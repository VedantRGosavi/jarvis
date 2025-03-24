# FridayAI Download Files Management

This document explains how to manage and update the download files for FridayAI application.

## Overview

FridayAI provides installers for different platforms:
- Windows (`.zip`)
- macOS (`.dmg`)
- Linux (`.tar.gz`)

Each platform has two versions:
- Latest (stable) version
- Beta version

## File Structure

All download files are stored in the `downloads/` directory:

```
downloads/
├── FridayAI-Win-latest.zip
├── FridayAI-Win-beta.zip
├── FridayAI-Mac-latest.dmg
├── FridayAI-Mac-beta.dmg
├── FridayAI-Linux-latest.tar.gz
├── FridayAI-Linux-beta.tar.gz
└── checksums.txt
```

The `checksums.txt` file contains SHA256 checksums for all files to verify integrity.

## Scripts

The following scripts are available to manage download files:

### 1. Building Installers

```bash
./scripts/build-installers.sh
```

This script generates all installer files and updates the checksums file. Use this script whenever you need to update the actual application installers.

In a production environment, you would replace the placeholder application files with your actual compiled application binaries before running this script.

### 2. Verifying Files

```bash
./scripts/verify-downloads.sh
```

This script verifies that:
- All required files exist
- Files have appropriate sizes (not empty/placeholder)
- File checksums match those in checksums.txt

Run this script before deploying to ensure all files are valid.

### 3. Deploying Files

```bash
./scripts/deploy-downloads.sh
```

This script:
1. Verifies files are valid
2. Creates a backup of current production files
3. Deploys new files to the production server
4. Sets appropriate file permissions

## Production Deployment Checklist

Before going live, ensure:

1. All placeholder files have been replaced with actual application binaries
2. All installers are verified with `verify-downloads.sh`
3. The API endpoint `/api/download` is properly secured
4. Rate limiting is enabled to prevent abuse
5. File integrity checks are working correctly

## Updating Application Versions

When releasing a new version:

1. Update your application binaries in the `build/` directory
2. Run `./scripts/build-installers.sh` to generate new installers
3. Verify files with `./scripts/verify-downloads.sh`
4. Deploy with `./scripts/deploy-downloads.sh`

## Troubleshooting

If checksums do not match after replacing application binaries:
1. Ensure binaries were properly copied
2. Run `./scripts/build-installers.sh` again to rebuild installers and update checksums
3. Check for any error messages during the build process

## Security Considerations

- Keep original application binaries in a secure location
- Regularly audit download logs for unusual patterns
- Monitor disk space to ensure downloads can be served
- Update checksums whenever files change
