# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-01-27

### Added

- Initial release
- Send text messages with URL preview support
- Send template messages with dynamic parameters
- Send media messages (images, videos, documents, audio)
- Send location messages
- Send interactive buttons (up to 3 buttons)
- Send interactive lists with sections
- Send reactions to messages
- Mark messages as read
- Comprehensive webhook handling for incoming messages
- Support for all WhatsApp message types (text, media, location, contacts, interactive, reactions)
- Message status tracking (sent, delivered, read, failed)
- Custom exception handling with detailed error information
- Automatic retry mechanism for failed requests
- Configurable API version, timeout, and retry settings
- Helper class for building message payloads
- Full documentation and examples
- MIT License

### Features

- PSR-4 autoloading
- Laravel auto-discovery support
- Facade support for easy access
- Comprehensive error handling
- Detailed logging for debugging
- Extensible webhook controller
- Configurable routes and middleware
