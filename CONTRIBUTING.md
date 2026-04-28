# Contributing

This SDK is **mirrored** from the Payvizio platform monorepo. Authoring
happens upstream; this public repo is the publish target.

## Reporting a bug or proposing a change

1. File a GitHub issue with a minimal repro, the SDK version, and the
   runtime version (Node 18.x, Python 3.11, Go 1.22, …).
2. For UX-only fixes (typos, examples), feel free to open a PR — we'll
   forward-port it to the upstream monorepo and re-mirror.
3. For functional changes (new endpoint, behavior change), the conversation
   is best had upstream — link the issue here and we'll route it.

## Why force-pushed history?

Each release pushes the monorepo's subtree split as a force-push so the
public repo stays an exact mirror of the upstream `sdks/<name>/` directory.
Don't merge directly into `main` here — your changes will be overwritten on
the next release.

## Versioning

Pre-1.0 — minor bumps may break the API. Pin a specific version in
production. Tags use plain semver (`v0.2.1`).
