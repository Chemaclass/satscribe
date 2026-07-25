# Release Automation

Releases are handled entirely through GitHub Actions. Every push to `main` triggers the [`deploy.yml`](workflows/deploy.yml) workflow, which connects to the VPS and runs `deploy-satscribe` — a wrapper around the `deploy.sh` script. This performs a zero-downtime deployment and keeps a history of timestamped releases.

There are no version tags: `main` is always what is live. User-visible changes are recorded in [CHANGELOG.md](../CHANGELOG.md).

> **Pushing to `main` deploys to production.** There is no staging step and no manual approval.

## Release Flow

```text
       Push to main
            |
            v
  GitHub Actions (deploy.yml)
            |
            v
        SSH to VPS
            |
            v
      ./deploy.sh
            |
            v
   /var/www/html/satscribe
```

The `deploy.sh` script clones the repository into a new directory under `releases/`, runs composer, builds assets and migrates the database. Once complete, the `current` symlink is atomically switched to the new release.

```
releases/
├── <timestamp1>
├── <timestamp2>
└── <timestampN>
current -> releases/<timestampN>
```

## Server Structure

```text
/var/www/html/satscribe
├── current -> releases/20250705105441
├── releases/
├── scripts/
└── shared/
```

## Nginx Config

```nginx
server {
    server_name satscribe.app www.satscribe.app;

    root /var/www/html/satscribe/current/public;
    index index.php index.html;

    location {
        ...
    }
}
```
