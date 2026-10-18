# Changelog

## 0.2.0

- **Org domain must be `https`**: `http://` and other schemes, paths, queries, fragments and userinfo are rejected on save and at login. Bare hosts get `https://`; the host is lowercased and `:443` dropped so it matches Okta's issuer.
- **Upgrade**: a stored Org Domain with a path (e.g. `…/oauth2/default`) or `http://` fails at login — move the server id to Custom Authorization Server and re-save the domain as a bare host.
- **`groups` scope is requested only from the org authorization server.** With a custom `auth_server`, define a `groups` claim on the server's Claims tab instead (fixes `invalid_scope`); a claim tied to a custom `groups` scope must switch to "Include in: Any scope".
- Requires `dmlab/module-admin-sso ^0.2` / `dmlab/module-sso-core ^0.2` (verified-email adoption).
- Packaging: `dmlab/module-sso-core` and `magento/module-config` declared, `DmLab_SsoCore` / `Magento_Config` sequenced, composer metadata, OSL-3.0 headers, i18n phrases.

## 0.1.1

- Rebrand to the `dmlab` vendor and `DmLab` namespace.
