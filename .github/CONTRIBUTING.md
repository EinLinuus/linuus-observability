# Contribution Guide

Thank you for considering contributing to linu.us Observability! Please review the following guidelines before submitting a pull request.

For significant changes, please open an issue first so we can discuss the approach.

## Process

1. Fork the project
2. Create a new branch
3. Code, test, commit, and push
4. Open a pull request detailing your changes

## Guidelines

- Ensure the coding style passes by running `composer lint`.
- Send a coherent commit history, making sure each commit in your pull request is meaningful.
- You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts.
- Please remember that we follow [SemVer](http://semver.org/).

## Setup

Clone your fork, then install the dev dependencies:

```bash
composer install
```

## Lint

Lint your code:

```bash
composer lint
```

## Tests

Run all tests:

```bash
composer test
```

## Releases

Publishing a GitHub release publishes the package to Packagist through the
`Publish to Packagist` workflow.

Configure these GitHub Actions repository secrets before publishing:

- `PACKAGIST_USERNAME`: the Packagist account username.
- `PACKAGIST_API_TOKEN`: the Packagist API token.

The first release registers the package and requires the main Packagist API
token. After that succeeds, replace the secret with the safe API token, which
has sufficient access for subsequent package updates.
