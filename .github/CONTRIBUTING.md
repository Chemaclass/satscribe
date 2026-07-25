# Contributing to satscribe

## We have a Code of Conduct

Please note that this project is released with a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in this project you agree to abide by its terms.

## Any contributions you make will be under the MIT License

When you submit code changes, your submissions are understood to be under the same [MIT](https://github.com/Chemaclass/satscribe/blob/main/LICENSE) that covers the project. By contributing to this project, you agree that your contributions will be licensed under its MIT.

## Write bug reports with detail, background, and sample code

In your bug report, please provide the following:

* A quick summary and/or background
* Steps to reproduce
    * Be specific!
    * Give sample code if you can.
* What you expected would happen
* What actually happens
* Notes (possibly including why you think this might be happening, or stuff you tried that didn't work)

Please post code and output as text ([using proper markup](https://guides.github.com/features/mastering-markdown/)). Additional screenshots to help contextualize behavior are ok.

## Workflow for Pull Requests

1. Fork/clone the repository.
2. Create your branch from `main` if you plan to implement new functionality or change existing code significantly.
3. Implement your change and add tests for it.
4. Ensure the test suite passes.
5. Ensure the code complies with our coding guidelines (see below).
6. Send that pull request!

Please make sure you have [set up your username and email address](https://git-scm.com/book/en/v2/Getting-Started-First-Time-Git-Setup) for use with Git. Strings such as `silly nick name <root@localhost>` looks bad in the commit history of a project.

## Change the configuration
To change the configuration for the project we use the `.env` file if you would like to know what variables should be there use the following command:
```bash
cp .env.example .env
```

## Setting up the project

You can run the application either with Docker or directly on your machine.

### Using Docker

1. Make sure Docker and Docker Compose are available.
2. Build and start the container:

```bash
docker-compose up --build
```

This command installs the PHP and Node dependencies, runs the database migrations and serves the app at `http://localhost:8000`.

### Local setup

If you prefer to work without Docker, run the following commands:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
composer dev
```

### How to help

- Open issues for any bugs you find or features you would like to see.
- Submit pull requests from a topic branch and include tests for new functionality.
- Review open pull requests and help improve the codebase.

## Coding guidelines

Business logic belongs in `modules/`, never in `app/`. Read
[docs/architecture.md](../docs/architecture.md) first — the layer contract and
the "interface first, repositories are the only Eloquent gateway" rules are what
most review comments are about.

Every PHP file declares `strict_types=1`, classes are `final` unless something
extends them, and immutable ones are `readonly`. Type declarations get a `T`
prefix (`TRawBlock`); input DTOs use a `Transfer` suffix and handler outputs a
`Result` suffix, with no prefix.

External data — API responses, webhook bodies, cache entries, session values,
request fields — is untyped and untrusted. Validate it where it enters rather
than casting `mixed` deeper in.

## Tests

Write the failing test first. Unit tests mirror `modules/` one-to-one under
`tests/Unit/`, mock the interfaces from each module's `Domain/`, and never touch
the database or the network. Anything needing the framework — routes, middleware,
repositories — is a feature test.

Use `mock()` directly rather than `Mockery::mock()`.

## The gate

```bash
composer fix && composer test
```

`composer test` runs PHPStan at level 9 over `app`, `modules` and `tests`,
followed by PHPUnit. Both must pass before a PR is reviewed. You can have this
run automatically on every commit:

```bash
git config core.hooksPath githooks
```

## Commits

Conventional commits, imperative mood, no trailing period:

```
<type>(<scope>): <subject>
```

`feat`, `fix`, `ref` (**not** `refactor`), `test`, `docs`, `chore`. Scope is the
module in lowercase when the change is confined to one, e.g.
`feat(chat): stream assistant replies`.

Add a `## Unreleased` entry to [CHANGELOG.md](../CHANGELOG.md) for anything a
user or operator would notice.
