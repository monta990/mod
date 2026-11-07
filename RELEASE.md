# Release version and tag policy

This rule applies to UI Branding and is the shared convention for the maintained GLPI plugin repositories.

## Numeric version only

Use the plain semantic version as the Git tag and GitHub Release name:

- Correct: `12.0.3`
- Incorrect: `v12.0.3`

The plugin metadata, release tag, GitHub Release title/version, changelog heading, runtime ZIP name and workflow tag filter must all refer to the same numeric version. Do not add a leading `v` to a plugin release tag.

Examples:

- Git tag: `12.0.3`
- GitHub Release: `12.0.3`
- Runtime package: `mod-12.0.3.zip`

This policy concerns plugin release versions. It does not prohibit the `v` text in third-party action version comments or in version identifiers belonging to external tools.

## Automation

Release workflows must trigger on the numeric tag and compare `github.ref` with `refs/tags/<version>`. Release validation should fail when a leading `v` is introduced into the plugin release tag.
