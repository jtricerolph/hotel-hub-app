# Hotel Hub App Icons

This directory should contain PWA icons for the Hotel Hub application.

## Required Icons

Generate the following icon sizes using the Material UI concierge icon:

### Standard Icons (any purpose)
- `icon-72x72.png` - 72x72px
- `icon-96x96.png` - 96x96px
- `icon-128x128.png` - 128x128px
- `icon-144x144.png` - 144x144px
- `icon-152x152.png` - 152x152px
- `icon-192x192.png` - 192x192px (also used for Apple Touch Icon)
- `icon-384x384.png` - 384x384px
- `icon-512x512.png` - 512x512px

### Maskable Icons
- `icon-192x192-maskable.png` - 192x192px with safe zone
- `icon-512x512-maskable.png` - 512x512px with safe zone

## Icon Design

Use the Material UI concierge bell icon with the following SVG path:

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <path d="M2 17h20v2H2v-2zm11.84-9.21c.1-.24.16-.51.16-.79 0-1.1-.9-2-2-2s-2 .9-2 2c0 .28.06.55.16.79C6.25 8.6 3.27 11.93 3 16h18c-.27-4.07-3.25-7.4-7.16-8.21z"/>
</svg>
```

**Important:** The SVG coordinate system is inverted on the Y-axis compared to standard coordinates. When generating icons, ensure the image is rendered correctly.

## Icon Generation Tools

You can use one of these tools to generate the icons:

1. **PWA Builder Image Generator**: https://www.pwabuilder.com/imageGenerator
2. **Real Favicon Generator**: https://realfavicongenerator.net/
3. **Manual with Figma/Sketch**: Create an artboard for each size and export as PNG

## Icon Requirements

- Format: PNG (24-bit with alpha transparency)
- Background: Solid color (use theme primary color: #2196f3)
- Foreground: White or contrasting color
- Padding: Standard icons should have ~10% padding, maskable icons need 20% safe zone
- Compression: Optimize PNGs to reduce file size

## Maskable Icons Safe Zone

For maskable icons, ensure the important content (the bell) stays within the central 80% of the canvas (20% safe zone on all sides). This prevents the icon from being cut off on devices that apply different masks.

## Testing

After generating icons, test them:
1. Visit https://manifest-validator.appspot.com/ to validate the manifest
2. Use Chrome DevTools > Application > Manifest to preview icons
3. Install the PWA on various devices to see how icons appear

## Notes

- Icons referenced in [class-hha-pwa.php](../../includes/class-hha-pwa.php)
- Icons also used in login template and admin interface
- Update `manifest.json` generation if icon paths change
