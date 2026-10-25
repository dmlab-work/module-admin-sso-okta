# DmLab_AdminSsoOkta

> Okta login for the Magento 2 admin panel.

[![CI](https://github.com/dmlab-work/module-admin-sso-okta/actions/workflows/ci.yml/badge.svg)](https://github.com/dmlab-work/module-admin-sso-okta/actions/workflows/ci.yml) [![codecov](https://codecov.io/gh/dmlab-work/module-admin-sso-okta/graph/badge.svg)](https://codecov.io/gh/dmlab-work/module-admin-sso-okta) [![Packagist Version](https://img.shields.io/packagist/v/dmlab/module-admin-sso-okta)](https://packagist.org/packages/dmlab/module-admin-sso-okta) [![Downloads](https://img.shields.io/packagist/dt/dmlab/module-admin-sso-okta)](https://packagist.org/packages/dmlab/module-admin-sso-okta) [![PHP](https://img.shields.io/packagist/dependency-v/dmlab/module-admin-sso-okta/php)](https://packagist.org/packages/dmlab/module-admin-sso-okta) [![License](https://img.shields.io/packagist/l/dmlab/module-admin-sso-okta)](LICENSE) ![Magento](https://img.shields.io/badge/Magento-2.4-orange)

A thin Okta provider plugin for the provider-agnostic `admin-sso` capability. It supplies the Okta OIDC preset — discovery from your org domain, scopes, `groups` claim, login-button branding — while all OIDC protocol lives in `sso-core` and all admin logic in `admin-sso`. Installing it pulls `admin-sso` and `sso-core` automatically.

## Installation

```bash
composer require dmlab/module-admin-sso-okta
bin/magento module:enable DmLab_SsoCore DmLab_AdminSso DmLab_AdminSsoOkta
bin/magento setup:upgrade
```

## Create the Okta OIDC app

In the Okta Admin Console → **Applications → Create App Integration**:

1. Sign-in method **OIDC - OpenID Connect**, application type **Web Application**.
2. **Sign-in redirect URI** — the `admin-sso` callback:
   `https://<admin-host>/<admin-path>/adminsso/sso/callback`
   (`<admin-path>` is the backend frontName, default `admin`). It must match the
   admin URL used at runtime exactly.
3. Save, then copy the **Client ID** and **Client secret**.
4. **Groups claim** — for IdP-group → ACL-role mapping. Without it Okta emits no
   groups and role mapping falls back to the default role.
   - **Org server** (no Custom Authorization Server set): in the app, **Sign On →
     OpenID Connect ID Token → Groups claim filter**, e.g. `Matches regex .*`. The
     preset requests the `groups` scope.
   - **Custom server**: **Security → API → Authorization Servers → `<server>` →
     Claims** → add `groups` (token type ID Token, include Always, value type Groups,
     filter e.g. `Matches regex .*`). The preset does not request the `groups` scope
     there.

Email adoption of an existing admin requires Okta's `email_verified=true`; an
unverified primary email is never adopted.

## Configuration

Admin → Stores → Configuration → **DMLab → Admin SSO**.

**General** (`dmlab_admin_sso/general/*`):

| Field | Value |
|---|---|
| Enable Admin SSO | Yes |
| Identity Provider | Okta |
| Client ID | from the Okta app |
| Client Secret | from the Okta app |

**Okta** (`dmlab_admin_sso/okta/*`, shown when Okta is selected):

| Field | Value |
|---|---|
| Org Domain | your org domain, e.g. `dev-123.okta.com` — `https` only (scheme optional; `http://` rejected) |
| Custom Authorization Server | optional server id (e.g. `default`); empty = org server |

The discovery URL is derived from these:
`https://<domain>/.well-known/openid-configuration`, or
`https://<domain>/oauth2/<auth-server>/.well-known/openid-configuration` with a
custom authorization server.

Group → role mapping and enforce-SSO/break-glass are configured in `admin-sso`; see
that module's README.

## Requirements

- Magento **2.4.x** (tested on 2.4.9)
- PHP **8.3 – 8.5**
- `dmlab/module-admin-sso` / `dmlab/module-sso-core` **^0.2** (installed automatically)

## Part of the DMLab identity suite

| Repo | Role |
|------|------|
| `sso-core` | Shared OIDC engine (installed automatically) |
| `admin-sso` · `admin-sso-<idp>` | Admin-panel SSO login |
| `customer-sso` · `customer-sso-<idp>` | Storefront SSO login |
| `admin-scim` · `admin-scim-<idp>` | Admin-user provisioning (SCIM 2.0) |

## License

[OSL-3.0](LICENSE) © DMLab. Commercial licensing and support: <https://dmlab.work>.
