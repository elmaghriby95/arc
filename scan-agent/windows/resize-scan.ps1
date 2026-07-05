param(
    [Parameter(Mandatory = $true)][string]$InputPath,
    [Parameter(Mandatory = $true)][string]$OutputPath,
    [int]$MaxWidth = 768,
    [int]$MaxHeight = 1050,
    [int]$Quality = 35
)

Add-Type -AssemblyName System.Drawing

$image = [System.Drawing.Image]::FromFile($InputPath)
$graphics = $null
$bitmap = $null

try {
    $ratioW = 1.0
    $ratioH = 1.0

    if ($image.Width -gt $MaxWidth) {
        $ratioW = $MaxWidth / [double]$image.Width
    }

    if ($image.Height -gt $MaxHeight) {
        $ratioH = $MaxHeight / [double]$image.Height
    }

    $ratio = [Math]::Min($ratioW, $ratioH)

    if ($ratio -lt 1.0) {
        $targetWidth = [int][Math]::Max(1, [Math]::Round($image.Width * $ratio))
        $targetHeight = [int][Math]::Max(1, [Math]::Round($image.Height * $ratio))
    } else {
        $targetWidth = $image.Width
        $targetHeight = $image.Height
    }

    $bitmap = New-Object System.Drawing.Bitmap $targetWidth, $targetHeight, ([System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBilinear
    $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighSpeed

    $colorMatrix = New-Object System.Drawing.Imaging.ColorMatrix
    $colorMatrix.Matrix00 = 0.299
    $colorMatrix.Matrix01 = 0.299
    $colorMatrix.Matrix02 = 0.299
    $colorMatrix.Matrix10 = 0.587
    $colorMatrix.Matrix11 = 0.587
    $colorMatrix.Matrix12 = 0.587
    $colorMatrix.Matrix20 = 0.114
    $colorMatrix.Matrix21 = 0.114
    $colorMatrix.Matrix22 = 0.114

    $attributes = New-Object System.Drawing.Imaging.ImageAttributes
    $attributes.SetColorMatrix($colorMatrix)

    $graphics.DrawImage(
        $image,
        (New-Object System.Drawing.Rectangle 0, 0, $targetWidth, $targetHeight),
        0,
        0,
        $image.Width,
        $image.Height,
        [System.Drawing.GraphicsUnit]::Pixel,
        $attributes
    )

    $encoder = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() |
        Where-Object { $_.MimeType -eq 'image/jpeg' } |
        Select-Object -First 1

    $clampedQuality = [long64][Math]::Max(32, [Math]::Min(55, $Quality))
    $encoderParams = New-Object System.Drawing.Imaging.EncoderParameters 1
    $encoderParams.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter(
        [System.Drawing.Imaging.Encoder]::Quality,
        $clampedQuality
    )

    $bitmap.Save($OutputPath, $encoder, $encoderParams)
} finally {
    if ($graphics) { $graphics.Dispose() }
    if ($bitmap) { $bitmap.Dispose() }
    $image.Dispose()
}
