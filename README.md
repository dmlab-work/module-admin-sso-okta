# DmLab_AdminSsoOkta

> Okta login for the Magento 2 admin panel.

![License](https://img.shields.io/badge/license-OSL--3.0-green) ![Magento](https://img.shields.io/badge/Magento-2.4-orange) ![PHP](https://img.shields.io/badge/PHP-8.3--8.5-blue) ![Version](https://img.shields.io/badge/version-0.0.1-lightgrey)

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
4. **Groups claim** — for IdP-group → ACL-role mapping, add a `groups` claim to the
   ID token: **Sign On → OpenID Connect ID Token → Groups claim**, filter e.g.
   `Matches regex .*`. Without this Okta emits no groups and role mapping falls back
   to the default role.

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
| Org Domain | your org domain, e.g. `dev-123.okta.com` (scheme optional, `https` assumed) |
| Custom Authorization Server | optional server id (e.g. `default`); empty = org server |

The discovery URL is derived from these:
`https://<domain>/.well-known/openid-configuration`, or
`https://<domain>/oauth2/<auth-server>/.well-known/openid-configuration` with a
custom authorization server.

Group → role mapping and enforce-SSO/break-glass are configured in `admin-sso`; see
that module's README.

## Requirements

- Magento **2.4.x**
- PHP **8.3 – 8.5**

## Part of the DMLab identity suite

| Repo | Role |
|------|------|
| `sso-core` | Shared OIDC engine (installed automatically) |
| `admin-sso` · `admin-sso-<idp>` | Admin-panel SSO login |
| `customer-sso` · `customer-sso-<idp>` | Storefront SSO login |
| `admin-scim` · `admin-scim-<idp>` | Admin-user provisioning (SCIM 2.0) |

## License

[OSL-3.0](LICENSE) © DMLab. Commercial licensing and support: <https://dmlab.work>.
