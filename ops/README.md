# Operations

Satscribe runs as a container on the shared VPS, behind the same `kamal-proxy`
as the other apps. Nothing about it lives on the host any more except the backup
job below.

## Deploying

Pushing to `main` deploys. The workflow builds the image on the VPS, pushes it
to ghcr, and switches traffic only once the new container answers `/up`.

```bash
gh workflow run Deploy          # redeploy without a commit
gh run watch                    # follow it
```

Kamal is pinned to 2.12.0 on purpose: `kamal-proxy` is shared with the other
apps on the box and a different version would upgrade that container out from
under them. Never run `kamal proxy reboot` or `kamal setup` from this repo.

```bash
bin/kamal app logs -f           # tail the running container
bin/kamal lock release          # clear a lock left by a deploy that died
```

## Backups

`ops/backup-db` is installed at `/usr/local/bin/backup-satscribe` and runs
nightly from `/etc/cron.d/satscribe-backup`, writing to
`/var/log/satscribe-backup.log`.

It snapshots with `VACUUM INTO` rather than copying the file. The database runs
in WAL mode, so the file on disk is only half the story and copying it while the
app writes yields a torn database.

```bash
sudo /usr/local/bin/backup-satscribe --verify    # take one by hand
ls -lh /var/backups/satscribe/                   # last 7 days
```

`--verify` gunzips the copy and compares row counts against the live database,
because a dump that was written is not the same as a dump that can bring
anything back.

**The copy is still on the same disk.** Set `BACKUP_REMOTE` in
`/etc/satscribe-backup.env` once an off-box destination exists, and the script
will push each backup there. Until then this protects against the app corrupting
its data, not against losing the machine.

## Restoring the database

Do this from a shell on the VPS. Read it once now rather than at 3am.

### 1. Pick the backup

```bash
ls -lh /var/backups/satscribe/
```

Filenames are UTC: `satscribe-20260822T143533Z.sqlite.gz`.

### 2. Check what is in it before trusting it

```bash
gunzip -c /var/backups/satscribe/<file>.sqlite.gz > /tmp/check.sqlite
php -r '
  $pdo = new PDO("sqlite:/tmp/check.sqlite");
  foreach (["chats","messages","payments"] as $t) {
    echo $t, "=", $pdo->query("SELECT count(*) FROM $t")->fetchColumn(), "\n";
  }'
```

### 3. Put it back

The database lives in the `satscribe_storage` volume, which outlives the
container. Stop the app first so nothing writes while the file is swapped.

```bash
C=$(docker ps -q --filter label=service=satscribe)
docker stop "$C"

docker run --rm -v satscribe_storage:/dest -v /tmp:/src alpine sh -c '
  cp /dest/database/database.sqlite /dest/database/database.sqlite.before-restore
  cp /src/check.sqlite /dest/database/database.sqlite
  chown 33:33 /dest/database/database.sqlite'

docker start "$C"
curl -s -o /dev/null -w '%{http_code}\n' https://satscribe.app/up
```

The previous file is kept alongside as `.before-restore`. Delete it once the
restore is confirmed good.

## Running the test suite

Local PHP is often newer than the one production runs, and `php-cs-fixer`
refuses to run on an unsupported version. `bin/test` runs the same gate CI runs,
in a PHP 8.2 container, so the result matches.

```bash
bin/test            # phpunit + phpstan
bin/test --fix      # also php-cs-fixer and rector
```

## Where things are

| Thing | Where |
|---|---|
| App container | `satscribe-web-<sha>`, found by `label=service=satscribe` |
| Database + storage | docker volume `satscribe_storage` |
| Database file | `/var/www/html/storage/database/database.sqlite` inside the container |
| Backups | `/var/backups/satscribe/` on the host |
| Backup config | `/etc/satscribe-backup.env` |
| Logs | `bin/kamal app logs`, and `/var/log/satscribe-backup.log` for the backup |
