# Changelog
## [Unreleased] - yyyy-mm-dd

### Added

### Changed

### Fixed

### Updated

## [10.2.4] - 2026-05-28


### Added
- username listener for password reset form

### Fixed
- prefill password reset username

## [10.2.3] - 2026-05-28


### Fixed
- ?? bug
- bug with empty os info

## [10.2.2] - 2026-05-27


### Fixed
- after update namespace

## [10.2.1] - 2026-05-26


### Changed
- PublicKeyCredentialUserEntity remove profile picture

### Fixed
- invalid chanllenge bug

## [10.2.0] - 2026-05-24


### Fixed
- array index bug

## [10.1.9] - 2026-05-23


### Fixed
- call login modal from wp_die

## [10.1.8] - 2026-05-23


### Fixed
- empty error

## [10.1.7] - 2026-05-21


### Fixed
- user->id -> ->ID

## [10.1.6] - 2026-05-14


### Added
- lostpassword_url filter to point to custom pw reset page

## [10.1.5] - 2026-05-14


### Changed
- date( to gmdate(

## [10.1.4] - 2026-05-13


### Fixed
- login errors
- login troubles

## [10.1.2] - 2026-05-12


### Fixed
- login modal

## [10.1.1] - 2026-05-12


### Changed
- permission callback for rest api

## [10.1.0] - 2026-05-12


### Changed
- login_modal not loaded before usage

## [10.0.9] - 2026-05-11


## [10.0.8] - 2026-05-11


### Fixed
- echo error

## [10.0.7] - 2026-05-08


### Fixed
- account page retrieval

## [10.0.6] - 2026-05-07


### Changed
- replaced sweetalert

### Fixed
- webauth login

## [10.0.5] - 2026-05-06


### Fixed
- url in js
- login issues

## [10.0.4] - 2026-05-05


## [10.0.3] - 2026-05-05


### Fixed
- plugin settings
- admin menu
- login menu items

## [10.0.1] - 2026-05-03


### Added
- support for block theme
- add login/logout block in block theme
- redirection to settings page on plugin activation

### Changed
- implemented wp_get_environment_type(
- module to plugin
- exclude .vscode from releases
- updated github workflow versions
- removed the redirection at activation as it is done by the share plugin
- use shared github workflows

### Fixed
- login issues

## [9.1.2] - 2026-03-05


### Changed
- fixed with login screen

## [9.1.1] - 2026-02-05


### Fixed
- login after autofill webauthn failed

## [9.1.0] - 2026-01-30


### Fixed
- webauthn as a second login factor

## [9.0.8] - 2026-01-29


### Added
- webauthn autofill

### Changed
- logged messages
- trying to fix autocomplete for webauthn

### Fixed
- startAuthentication params

## [9.0.7] - 2025-12-05


### Changed
- register webauthn on login screen

## [9.0.6] - 2025-12-02


### Changed
- only add method when it is not there yet

### Fixed
- 2fa error
- reregistering webauthn

## [9.0.4] - 2025-12-01


## [9.0.3] - 2025-11-29


### Fixed
- 2fa bug

## [9.0.2] - 2025-11-27


### Fixed
- bug in pre updates
- registering authenticator
- positional account switcher dependency

## [9.0.1] - 2025-11-27


### Changed
- pre update file location
- also check new version before running pre update
- update lib before upgrading

### Fixed
- saving 2fa settings

## [8.4.9] - 2025-11-26


### Changed
- composer updated
- added cred metas retrieval
- webauthn version bump from 3.3 tot 5.2
- implemented getFromTransient
- removed admin_email_check_interval false return

### Fixed
- login problems
- get challenge
- update flow
- merging issues

## [8.4.8] - 2025-11-21


### Added
- support for Local

### Changed
- show register modal when ready

### Fixed

### Updated

## [8.4.7] - 2025-11-04


### Added
- support for the content filter module

## [8.4.6] - 2025-11-03


### Fixed
- css bug

## [8.4.5] - 2025-11-03


### Fixed
- always load assets

## [8.4.4] - 2025-11-03


### Changed
- stop listening to events if we have a match
- removed error and success layout

## [8.4.3] - 2025-10-31


### Changed
- render loader image using js

## [8.4.2] - 2025-10-30


### Changed
- use upgrade.php not install-helper.php
- only load 2 factor qr if needed

## [8.4.1] - 2025-10-23


### Fixed
- register webauth padding

## [8.4.0] - 2025-10-17


### Fixed
- always show loader
- to not import directly from login.js

## [8.3.9] - 2025-10-13


### Changed
- classnames
- data attribute names
- variable names

### Fixed
- bugs
- rest api call

## [8.3.8] - 2025-10-03


### Fixed
- reset login form

## [8.3.7] - 2025-10-02


### Fixed
- e-mail login

## [8.3.6] - 2025-10-02


### Changed
- code refactoring to js class

## [8.3.5] - 2025-09-26


### Changed
- classnames replace _ with -
- better login experience

## [8.3.4] - 2025-09-25


## [8.3.3] - 2025-09-24


## [8.3.1] - 2025-08-18


### Fixed
- bug in webauthentication

## [8.3.0] - 2025-08-18


### Fixed
- only listen to return key if button not disabled

## [8.2.9] - 2025-08-18


### Changed
- do not run authenticate filter after webauth

### Fixed
- bug in login

## [8.2.8] - 2025-08-15


### Added
- 'sim-login-menu-item' filter

## [8.2.7] - 2025-08-15


### Added
- show registration page link in settings

### Changed

### Updated

## [8.2.6] - 2025-08-14


### Added
- captcha on login
- only show reset password form elements when clicked

### Changed
- js imports

## [8.2.5] - 2025-04-09


### Fixed
- redirect issues

## [8.2.4] - 2025-04-09


### Fixed
- ask for passkey only once
- checkCredentioals when no 2fa
- password reset key with hash when already logged in
- issue with webauthn registering failures

## [8.2.3] - 2025-03-24


### Changed
- layout

## [8.2.2] - 2025-03-24


### Changed
- signal dependency
- force to register webauthn

## [8.2.1] - 2025-02-13


### Changed
- module hooks now include module slug

## [8.2.0] - 2025-02-13


## [8.1.9] - 2025-02-11


### Changed
- sim_module_updated filter to new format

## [8.1.8] - 2024-12-09


### Fixed
- password reset

## [8.1.7] - 2024-12-04


### Changed
- removed auto webauth

## [8.1.6] - 2024-11-29


### Fixed
- login cookie problems

## [8.1.5] - 2024-11-27


### Fixed
- post password login

## [8.1.4] - 2024-11-20


### Changed
- remove anonymous functions

### Fixed
- logout bug

## [8.1.3] - 2024-11-14


### Changed
- auto webauth when login modal is open

### Fixed
- login redirect

## [8.1.2] - 2024-11-01


### Fixed
- some bugs

## [8.1.1] - 2024-11-01


### Fixed
- webauth login
- input color in darkmode

## [8.1.0] - 2024-11-01


### Fixed
- login on mobile

## [8.0.9] - 2024-10-30


### Fixed
- login problems

## [8.0.8] - 2024-10-24


### Added
- login modal to login and continue on the same page

## [8.0.7] - 2024-10-18


### Fixed
- login issue

## [8.0.6] - 2024-10-17


### Changed
- readme

## [8.0.5] - 2024-10-17


### Fixed
- js bug

### Updated
- readme

## [8.04] - 2024-10-17


### Added
- qr code login
- qr code login count down timer

### Changed
- code cleanup
- qr code login url

### Fixed
- approve qr login url

## [8.0.3] - 2024-10-11


### Added
- redirect to admin check screen

## [8.0.2] - 2024-10-11


### Changed
- enqueing of styles and scripts
- auto login when login button clicked

## [8.0.1] - 2024-10-09


### Added

### Changed

### Fixed

## [8.0.0] - 2024-10-04


## [8.0.0] - 2024-10-03
