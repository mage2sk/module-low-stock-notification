# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.5] - 2026-10-03

### Fixed
- The product page stock alert block reads the form key from the form key service. Before, `getFormKey()` read a property the block does not have and would fail when called.
