# Security Policy

## Reporting a vulnerability

Email **security@payvizio.com** with:

- A description of the issue and the SDK / version affected
- Steps to reproduce (proof-of-concept code if possible)
- Impact you observed or expect

We acknowledge within **2 business days** and aim to ship a fix or
mitigation within **14 days** for high-severity issues. Please don't
file a public GitHub issue for security reports.

## Scope

In scope:

- Anything that could lead to incorrect signature verification, accidental
  PAN/CVV exposure, token/secret leakage, or cross-tenant data access.
- Dependency vulnerabilities reachable from a default install.

Out of scope:

- Issues only reproducible against a self-hosted PayVizio backend at versions
  older than the latest release.
- Best-practice nits without a concrete impact.

## Supported versions

We maintain the latest **0.x** release line. Pre-1.0; once 1.0 ships we'll
maintain the latest two **major** versions for security patches.
