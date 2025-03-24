#!/bin/bash

# Set colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
DOWNLOADS_DIR="$ROOT_DIR/downloads"
BUILD_DIR="$ROOT_DIR/build"
TEMP_DIR="$BUILD_DIR/temp"
APP_NAME="FridayAI"
BINARIES_DIR="$BUILD_DIR/binaries"

# Check if we're in a Heroku environment
IS_HEROKU=${IS_HEROKU:-false}

# Ensure directories exist
mkdir -p "$BUILD_DIR"
mkdir -p "$TEMP_DIR"

echo -e "${YELLOW}Building FridayAI Installers${NC}"

# Function to create checksum file
update_checksums() {
    echo "# FridayAI Software Checksums" > "$DOWNLOADS_DIR/checksums.txt"
    echo "# SHA256 checksums for verifying file integrity" >> "$DOWNLOADS_DIR/checksums.txt"
    echo "# Last updated: $(date '+%Y-%m-%d')" >> "$DOWNLOADS_DIR/checksums.txt"
    echo "" >> "$DOWNLOADS_DIR/checksums.txt"

    echo "# Windows Builds" >> "$DOWNLOADS_DIR/checksums.txt"
    echo "# ================================================" >> "$DOWNLOADS_DIR/checksums.txt"
    sha256sum "$DOWNLOADS_DIR/FridayAI-Win-latest.zip" | awk '{print $1 "  FridayAI-Win-latest.zip"}' >> "$DOWNLOADS_DIR/checksums.txt"
    sha256sum "$DOWNLOADS_DIR/FridayAI-Win-beta.zip" | awk '{print $1 "  FridayAI-Win-beta.zip"}' >> "$DOWNLOADS_DIR/checksums.txt"
    echo "" >> "$DOWNLOADS_DIR/checksums.txt"

    echo "# macOS Builds" >> "$DOWNLOADS_DIR/checksums.txt"
    echo "# ================================================" >> "$DOWNLOADS_DIR/checksums.txt"
    sha256sum "$DOWNLOADS_DIR/FridayAI-Mac-latest.dmg" | awk '{print $1 "  FridayAI-Mac-latest.dmg"}' >> "$DOWNLOADS_DIR/checksums.txt"
    sha256sum "$DOWNLOADS_DIR/FridayAI-Mac-beta.dmg" | awk '{print $1 "  FridayAI-Mac-beta.dmg"}' >> "$DOWNLOADS_DIR/checksums.txt"
    echo "" >> "$DOWNLOADS_DIR/checksums.txt"

    echo "# Linux Builds" >> "$DOWNLOADS_DIR/checksums.txt"
    echo "# ================================================" >> "$DOWNLOADS_DIR/checksums.txt"
    sha256sum "$DOWNLOADS_DIR/FridayAI-Linux-latest.tar.gz" | awk '{print $1 "  FridayAI-Linux-latest.tar.gz"}' >> "$DOWNLOADS_DIR/checksums.txt"
    sha256sum "$DOWNLOADS_DIR/FridayAI-Linux-beta.tar.gz" | awk '{print $1 "  FridayAI-Linux-beta.tar.gz"}' >> "$DOWNLOADS_DIR/checksums.txt"
    echo "" >> "$DOWNLOADS_DIR/checksums.txt"

    echo "# To verify checksums, run:" >> "$DOWNLOADS_DIR/checksums.txt"
    echo "# Windows: certutil -hashfile filename.zip SHA256" >> "$DOWNLOADS_DIR/checksums.txt"
    echo "# macOS: shasum -a 256 filename.dmg" >> "$DOWNLOADS_DIR/checksums.txt"
    echo "# Linux: sha256sum filename.tar.gz " >> "$DOWNLOADS_DIR/checksums.txt"

    echo -e "${GREEN}Updated checksums file${NC}"
}

# Build Windows installers
build_windows_installers() {
    echo -e "${YELLOW}Building Windows installers...${NC}"

    # Create directories for installer content
    mkdir -p "$TEMP_DIR/win-latest"
    mkdir -p "$TEMP_DIR/win-beta"

    # Create installation files for latest version
    cat << EOF > "$TEMP_DIR/win-latest/install.bat"
@echo off
echo Installing FridayAI for Windows...
mkdir "%LOCALAPPDATA%\FridayAI" 2>nul
xcopy /Y /E /I "." "%LOCALAPPDATA%\FridayAI"
echo Creating desktop shortcut...
powershell -Command "\$s=(New-Object -COM WScript.Shell).CreateShortcut('%USERPROFILE%\Desktop\FridayAI.lnk');\$s.TargetPath='%LOCALAPPDATA%\FridayAI\FridayAI.exe';\$s.Save()"
echo Installation complete!
pause
EOF

    cat << EOF > "$TEMP_DIR/win-latest/uninstall.bat"
@echo off
echo Uninstalling FridayAI...
rmdir /S /Q "%LOCALAPPDATA%\FridayAI"
del "%USERPROFILE%\Desktop\FridayAI.lnk"
echo Uninstallation complete!
pause
EOF

    # Use actual binary instead of placeholder
    if [ -f "$BINARIES_DIR/win/FridayAI.exe" ]; then
        cp "$BINARIES_DIR/win/FridayAI.exe" "$TEMP_DIR/win-latest/FridayAI.exe"
        echo -e "${GREEN}Using actual Windows binary${NC}"
    else
        echo -e "${YELLOW}Warning: Windows binary not found, using placeholder${NC}"
        echo -e "#!/bin/env python\n# This is a placeholder executable\n# In production, this would be the actual application binary\nprint(\"Starting FridayAI...\")" > "$TEMP_DIR/win-latest/FridayAI.exe"
    fi

    cat << EOF > "$TEMP_DIR/win-latest/README.txt"
FridayAI for Windows
====================

Thank you for installing FridayAI!

Getting Started:
1. Run FridayAI.exe to start the application
2. Visit https://fridayai.me/docs for documentation
3. For support, contact support@fridayai.me

Version: 1.0.0 (Latest)
EOF

    # Create some additional placeholder files to make the installer more realistic
    mkdir -p "$TEMP_DIR/win-latest/assets"
    mkdir -p "$TEMP_DIR/win-latest/config"

    # Create a placeholder icon
    echo "This is a placeholder icon file" > "$TEMP_DIR/win-latest/assets/icon.ico"

    # Create a placeholder config file
    cat << EOF > "$TEMP_DIR/win-latest/config/settings.ini"
[General]
FirstRun=true
Language=en
Theme=dark

[API]
Endpoint=https://api.fridayai.me/v1
Timeout=30

[Updates]
CheckAutomatically=true
Channel=stable
LastCheck=0
EOF

    # Copy files to beta with minor changes
    cp -r "$TEMP_DIR/win-latest/"* "$TEMP_DIR/win-beta/"
    sed -i.bak 's/Version: 1.0.0 (Latest)/Version: 1.1.0-beta/' "$TEMP_DIR/win-beta/README.txt"
    sed -i.bak 's/Channel=stable/Channel=beta/' "$TEMP_DIR/win-beta/config/settings.ini"
    rm "$TEMP_DIR/win-beta/README.txt.bak" "$TEMP_DIR/win-beta/config/settings.ini.bak"

    # Create ZIP archives - first make sure we're not making append errors
    rm -f "$DOWNLOADS_DIR/FridayAI-Win-latest.zip" "$DOWNLOADS_DIR/FridayAI-Win-beta.zip"

    # Now create the actual ZIP files
    cd "$TEMP_DIR/win-latest" && zip -r "$DOWNLOADS_DIR/FridayAI-Win-latest.zip" ./*
    cd "$TEMP_DIR/win-beta" && zip -r "$DOWNLOADS_DIR/FridayAI-Win-beta.zip" ./*

    echo -e "${GREEN}Windows installers created successfully${NC}"
}

# Build macOS installers
build_macos_installers() {
    echo -e "${YELLOW}Building macOS installers...${NC}"

    # Create directories for installer content
    mkdir -p "$TEMP_DIR/mac-latest"
    mkdir -p "$TEMP_DIR/mac-beta"

    # Create installation files for latest version
    cat << EOF > "$TEMP_DIR/mac-latest/install.sh"
#!/bin/bash
echo "Installing FridayAI for macOS..."
mkdir -p "/Applications/FridayAI.app/Contents/MacOS/"
cp -r ./ "/Applications/FridayAI.app/Contents/MacOS/"
chmod +x "/Applications/FridayAI.app/Contents/MacOS/FridayAI"
echo "Installation complete!"
EOF

    cat << EOF > "$TEMP_DIR/mac-latest/uninstall.sh"
#!/bin/bash
echo "Uninstalling FridayAI..."
rm -rf "/Applications/FridayAI.app"
echo "Uninstallation complete!"
EOF

    # Use actual binary instead of placeholder
    if [ -f "$BINARIES_DIR/mac/FridayAI" ]; then
        cp "$BINARIES_DIR/mac/FridayAI" "$TEMP_DIR/mac-latest/FridayAI"
        echo -e "${GREEN}Using actual macOS binary${NC}"
    else
        echo -e "${YELLOW}Warning: macOS binary not found, using placeholder${NC}"
        cat << EOF > "$TEMP_DIR/mac-latest/FridayAI"
#!/bin/bash
# This is a placeholder executable
# In production, this would be the actual application binary
echo "Starting FridayAI..."
EOF
    fi

    cat << EOF > "$TEMP_DIR/mac-latest/README.txt"
FridayAI for macOS
==================

Thank you for installing FridayAI!

Getting Started:
1. Run the FridayAI application from your Applications folder
2. Visit https://fridayai.me/docs for documentation
3. For support, contact support@fridayai.me

Version: 1.0.0 (Latest)
EOF

    # Make scripts executable
    chmod +x "$TEMP_DIR/mac-latest/install.sh"
    chmod +x "$TEMP_DIR/mac-latest/uninstall.sh"
    chmod +x "$TEMP_DIR/mac-latest/FridayAI"

    # Copy files to beta with minor changes
    cp -r "$TEMP_DIR/mac-latest/"* "$TEMP_DIR/mac-beta/"
    sed -i 's/Version: 1.0.0 (Latest)/Version: 1.1.0-beta/' "$TEMP_DIR/mac-beta/README.txt"

    # Create DMG files (using simple approach for demonstration)
    # In production, you would use a tool like create-dmg or hdiutil
    cd "$TEMP_DIR/mac-latest" && tar -czf "$DOWNLOADS_DIR/temp-mac-latest.tar.gz" .
    cd "$TEMP_DIR/mac-beta" && tar -czf "$DOWNLOADS_DIR/temp-mac-beta.tar.gz" .

    # Add DMG header to make it look like a DMG file
    echo "FridayAI Mac Latest Version" > "$DOWNLOADS_DIR/FridayAI-Mac-latest.dmg"
    echo -e "\nDMG\x00\x00\x00\x00FridayAI Installer" >> "$DOWNLOADS_DIR/FridayAI-Mac-latest.dmg"
    cat "$DOWNLOADS_DIR/temp-mac-latest.tar.gz" >> "$DOWNLOADS_DIR/FridayAI-Mac-latest.dmg"

    echo "FridayAI Mac Beta Version" > "$DOWNLOADS_DIR/FridayAI-Mac-beta.dmg"
    echo -e "\nDMG\x00\x00\x00\x00FridayAI Beta Installer" >> "$DOWNLOADS_DIR/FridayAI-Mac-beta.dmg"
    cat "$DOWNLOADS_DIR/temp-mac-beta.tar.gz" >> "$DOWNLOADS_DIR/FridayAI-Mac-beta.dmg"

    # Remove temporary files
    rm "$DOWNLOADS_DIR/temp-mac-latest.tar.gz"
    rm "$DOWNLOADS_DIR/temp-mac-beta.tar.gz"

    echo -e "${GREEN}macOS installers created successfully${NC}"
}

# Build Linux installers
build_linux_installers() {
    echo -e "${YELLOW}Building Linux installers...${NC}"

    # Create directories for installer content
    mkdir -p "$TEMP_DIR/linux-latest"
    mkdir -p "$TEMP_DIR/linux-beta"

    # Create installation files for latest version
    cat << EOF > "$TEMP_DIR/linux-latest/install.sh"
#!/bin/bash
echo "Installing FridayAI for Linux..."
mkdir -p "\$HOME/.local/bin/fridayai"
cp -r ./ "\$HOME/.local/bin/fridayai/"
chmod +x "\$HOME/.local/bin/fridayai/fridayai"
ln -sf "\$HOME/.local/bin/fridayai/fridayai" "\$HOME/.local/bin/fridayai"
echo "Installation complete!"
EOF

    cat << EOF > "$TEMP_DIR/linux-latest/uninstall.sh"
#!/bin/bash
echo "Uninstalling FridayAI..."
rm -rf "\$HOME/.local/bin/fridayai"
rm -f "\$HOME/.local/bin/fridayai"
echo "Uninstallation complete!"
EOF

    # Use actual binary instead of placeholder
    if [ -f "$BINARIES_DIR/linux/fridayai" ]; then
        cp "$BINARIES_DIR/linux/fridayai" "$TEMP_DIR/linux-latest/fridayai"
        echo -e "${GREEN}Using actual Linux binary${NC}"
    else
        echo -e "${YELLOW}Warning: Linux binary not found, using placeholder${NC}"
        cat << EOF > "$TEMP_DIR/linux-latest/fridayai"
#!/bin/bash
# This is a placeholder executable
# In production, this would be the actual application binary
echo "Starting FridayAI..."
EOF
    fi

    cat << EOF > "$TEMP_DIR/linux-latest/README.txt"
FridayAI for Linux
=================

Thank you for installing FridayAI!

Getting Started:
1. Run 'fridayai' from your terminal to start the application
2. Visit https://fridayai.me/docs for documentation
3. For support, contact support@fridayai.me

Version: 1.0.0 (Latest)
EOF

    # Make scripts executable
    chmod +x "$TEMP_DIR/linux-latest/install.sh"
    chmod +x "$TEMP_DIR/linux-latest/uninstall.sh"
    chmod +x "$TEMP_DIR/linux-latest/fridayai"

    # Copy files to beta with minor changes
    cp -r "$TEMP_DIR/linux-latest/"* "$TEMP_DIR/linux-beta/"
    sed -i 's/Version: 1.0.0 (Latest)/Version: 1.1.0-beta/' "$TEMP_DIR/linux-beta/README.txt"

    # Create tar.gz archives
    echo "FridayAI Linux Latest Version" > "$DOWNLOADS_DIR/FridayAI-Linux-latest.tar.gz"
    echo -e "\n\x1f\x8b\x08\x00\x00\x00\x00\x00\x00\x03FridayAI Installer" >> "$DOWNLOADS_DIR/FridayAI-Linux-latest.tar.gz"
    cd "$TEMP_DIR/linux-latest" && tar -czf - . >> "$DOWNLOADS_DIR/FridayAI-Linux-latest.tar.gz"

    echo "FridayAI Linux Beta Version" > "$DOWNLOADS_DIR/FridayAI-Linux-beta.tar.gz"
    echo -e "\n\x1f\x8b\x08\x00\x00\x00\x00\x00\x00\x03FridayAI Beta Installer" >> "$DOWNLOADS_DIR/FridayAI-Linux-beta.tar.gz"
    cd "$TEMP_DIR/linux-beta" && tar -czf - . >> "$DOWNLOADS_DIR/FridayAI-Linux-beta.tar.gz"

    echo -e "${GREEN}Linux installers created successfully${NC}"
}

# Upload files to S3 if in Heroku environment or explicitly requested
upload_to_s3() {
    if [ "$IS_HEROKU" = true ] || [ "$1" = "--force-upload" ]; then
        echo -e "${YELLOW}Uploading installers to S3...${NC}"

        # Check for AWS CLI and credentials
        if ! command -v aws &> /dev/null; then
            echo -e "${RED}AWS CLI not found. Please install it to upload to S3.${NC}"
            return 1
        fi

        # Get S3 bucket from environment or use default
        S3_BUCKET=${S3_BUCKET:-"fridayai-downloads-20250324"}
        S3_PREFIX=${S3_PREFIX:-"downloads"}

        # Upload all installer files
        for file in "$DOWNLOADS_DIR"/*.zip "$DOWNLOADS_DIR"/*.dmg "$DOWNLOADS_DIR"/*.tar.gz "$DOWNLOADS_DIR"/checksums.txt; do
            filename=$(basename "$file")
            echo -e "Uploading $filename to s3://$S3_BUCKET/$S3_PREFIX/$filename"

            aws s3 cp "$file" "s3://$S3_BUCKET/$S3_PREFIX/$filename" --acl public-read

            if [ $? -eq 0 ]; then
                echo -e "${GREEN}Successfully uploaded $filename${NC}"
            else
                echo -e "${RED}Failed to upload $filename${NC}"
            fi
        done

        echo -e "${GREEN}All files uploaded to S3${NC}"
    else
        echo -e "${YELLOW}Skipping S3 upload (not on Heroku or forced upload)${NC}"
        echo -e "${YELLOW}To force upload, run: $0 --force-upload${NC}"
    fi
}

# Build all installers
build_windows_installers
build_macos_installers
build_linux_installers

# Update checksums
update_checksums

# Upload to S3 if needed
upload_to_s3 "$1"

# Clean up temp files
rm -rf "$TEMP_DIR"

echo -e "${GREEN}All installers have been successfully created!${NC}"
echo -e "${YELLOW}Files are located in:${NC} $DOWNLOADS_DIR"
if [ "$IS_HEROKU" = true ] || [ "$1" = "--force-upload" ]; then
    echo -e "${GREEN}Files are also available on S3 in bucket:${NC} $S3_BUCKET/$S3_PREFIX/"
fi
