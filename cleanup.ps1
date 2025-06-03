# Cleanup script to remove unnecessary files for bolt.new

# Remove large files and directories
$itemsToRemove = @(
    "fix.zip",
    "node_modules",
    "vendor",
    "uploads",
    "logs",
    "package-lock.json",
    "composer.lock",
    "yarn.lock"
)

foreach ($item in $itemsToRemove) {
    if (Test-Path $item) {
        Write-Host "Removing $item..."
        Remove-Item -Recurse -Force $item -ErrorAction SilentlyContinue
    }
}

# Clean up cache and temporary files
Get-ChildItem -Path . -Include ("*.tmp", "*.temp", "*.log", "*.cache") -Recurse | Remove-Item -Force

Write-Host "Cleanup complete. Project size reduced."
