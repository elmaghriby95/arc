param(
    [Parameter(Mandatory = $true)][string]$InputPath,
    [Parameter(Mandatory = $true)][string]$OutputPath,
    [int]$MaxWidth = 992,
    [int]$Quality = 48
)

Add-Type -AssemblyName System.Drawing

$image = [System.Drawing.Image]::FromFile($InputPath)
$graphics = $null
$bitmap = $null

try {
    if ($image.Width -le $MaxWidth) {
        $targetWidth = $image.Width
        $targetHeight = $image.Height
    } else {
        $ratio = $MaxWidth / [double]$image.Width
        $targetWidth = $MaxWidth
        $targetHeight = [int][Math]::Max(1, [Math]::Round($image.Height * $ratio))
    }

    $bitmap = New-Object System.Drawing.Bitmap $targetWidth, $targetHeight, ([System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic

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

    $clampedQuality = [long64][Math]::Max(35, [Math]::Min(75, $Quality))
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
