<script>
window.renderC128 = function (target, code, stickerWidthIn) {
    code = String(code || '').trim();
    if (!code) return false;
    var inch = stickerWidthIn > 0 ? stickerWidthIn : 1.5;
    var usablePx = inch * 96 * 0.90;
    var modules = (code.length * 11) + 35;
    var barW = usablePx / modules;
    if (barW > 2.0) barW = 2.0;
    if (barW < 1.05) barW = 1.05;
    var quiet = Math.max(4, Math.min(6, Math.round(barW * 4)));
    var barH = Math.max(26, Math.min(38, inch * 96 * 0.36));
    try {
        JsBarcode(target, code, {
            format: 'CODE128',
            width: barW,
            height: barH,
            displayValue: true,
            fontSize: 9,
            margin: quiet,
            marginTop: 0,
            marginBottom: 0,
            textMargin: 1,
            lineColor: '#000000',
            background: '#ffffff'
        });
        return true;
    } catch (e) {
        return false;
    }
};
</script>
