# Merge all fragment files into th.php and en.php (UTF-8 safe)
$fragDir = "C:\Users\WD11\Documents\Windsurf\hr.advws.com\app\lang\_fragments"
$thFile = "C:\Users\WD11\Documents\Windsurf\hr.advws.com\app\lang\th.php"
$enFile = "C:\Users\WD11\Documents\Windsurf\hr.advws.com\app\lang\en.php"
$utf8NoBom = New-Object System.Text.UTF8Encoding $false

# Collect all fragment keys
$fragments = [ordered]@{}
$fragFiles = Get-ChildItem $fragDir -Filter "*.php" | Where-Object { $_.Name -notlike "_*" } | Sort-Object Name
foreach ($f in $fragFiles) {
    $content = [System.IO.File]::ReadAllText($f.FullName, $utf8NoBom)
    # Match 'key' => ['en' => 'value', 'th' => 'value']
    # Handle both single and double quotes, and escaped quotes
    $pattern = "'([a-z0-9_.]+)'\s*=>\s*\[\s*'en'\s*=>\s*((?:'[^'\\]*(?:\\.[^'\\]*)*')|(?:""[^""\\]*(?:\\.[^""\\]*)*""))\s*,\s*'th'\s*=>\s*((?:'[^'\\]*(?:\\.[^'\\]*)*')|(?:""[^""\\]*(?:\\.[^""\\]*)*""))\s*\]"
    $matches = [regex]::Matches($content, $pattern)
    foreach ($m in $matches) {
        $key = $m.Groups[1].Value
        $enVal = $m.Groups[2].Value
        $thVal = $m.Groups[3].Value
        # Strip surrounding quotes
        if ($enVal.StartsWith("'") -and $enVal.EndsWith("'")) { $enVal = $enVal.Substring(1, $enVal.Length - 2) }
        elseif ($enVal.StartsWith('"') -and $enVal.EndsWith('"')) { $enVal = $enVal.Substring(1, $enVal.Length - 2) }
        if ($thVal.StartsWith("'") -and $thVal.EndsWith("'")) { $thVal = $thVal.Substring(1, $thVal.Length - 2) }
        elseif ($thVal.StartsWith('"') -and $thVal.EndsWith('"')) { $thVal = $thVal.Substring(1, $thVal.Length - 2) }
        # Unescape
        $enVal = $enVal -replace "\\'", "'"
        $thVal = $thVal -replace "\\'", "'"
        if (-not $fragments.Contains($key)) {
            $fragments[$key] = @{ en = $enVal; th = $thVal }
        }
    }
}
Write-Output "Total fragment keys collected: $($fragments.Count)"

# Load existing keys from both lang files
$existingKeys = @{}
foreach ($file in @($thFile, $enFile)) {
    $content = [System.IO.File]::ReadAllText($file, $utf8NoBom)
    $matches = [regex]::Matches($content, "'([a-z0-9_.]+)'\s*=>")
    foreach ($m in $matches) { $existingKeys[$m.Groups[1].Value] = $true }
}
Write-Output "Existing keys in lang files: $($existingKeys.Count)"

# Filter to only new keys
$newKeys = [ordered]@{}
foreach ($key in $fragments.Keys) {
    if (-not $existingKeys.ContainsKey($key)) {
        $newKeys[$key] = $fragments[$key]
    }
}
Write-Output "New keys to append: $($newKeys.Count)"

# Build insertion lines
$thLines = @()
$enLines = @()
foreach ($key in $newKeys.Keys) {
    $enEscaped = $newKeys[$key].en -replace "'", "\'"
    $thEscaped = $newKeys[$key].th -replace "'", "\'"
    $thLines += "    '$key' => '$thEscaped',"
    $enLines += "    '$key' => '$enEscaped',"
}

# Append to th.php — files end with "];" (return [ ... ]; format)
$thContent = [System.IO.File]::ReadAllText($thFile, $utf8NoBom)
$thPos = $thContent.LastIndexOf("];")
if ($thPos -ge 0) {
    $before = $thContent.Substring(0, $thPos)
    $after = $thContent.Substring($thPos)
    $insert = "`n    // --- Merged from fragments ---`n" + ($thLines -join "`n") + "`n"
    $newThContent = $before + $insert + $after
    [System.IO.File]::WriteAllText($thFile, $newThContent, $utf8NoBom)
    Write-Output "Appended $($thLines.Count) keys to th.php"
} else {
    Write-Output "ERROR: Could not find ]; in th.php"
}

# Append to en.php
$enContent = [System.IO.File]::ReadAllText($enFile, $utf8NoBom)
$enPos = $enContent.LastIndexOf("];")
if ($enPos -ge 0) {
    $before = $enContent.Substring(0, $enPos)
    $after = $enContent.Substring($enPos)
    $insert = "`n    // --- Merged from fragments ---`n" + ($enLines -join "`n") + "`n"
    $newEnContent = $before + $insert + $after
    [System.IO.File]::WriteAllText($enFile, $newEnContent, $utf8NoBom)
    Write-Output "Appended $($enLines.Count) keys to en.php"
} else {
    Write-Output "ERROR: Could not find ]; in en.php"
}

Write-Output "Merge complete."
