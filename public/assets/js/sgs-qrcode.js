/**
 * SGS Official Centralized QR Code Component
 * Reuses qrcode.min.js with error correction level H and centered school logo overlay.
 */

window.SGSQrCode = {
    /**
     * Generates a QR Code in a target container element.
     *
     * @param {HTMLElement|string} container Target DOM element or string element ID
     * @param {Object} options Configuration options
     * @param {string} options.text Data string or URL to encode
     * @param {number} [options.width=80] QR code width in pixels
     * @param {number} [options.height=80] QR code height in pixels
     * @param {string} [options.logoUrl=null] URL of centered school logo
     * @param {number} [options.logoRatio=0.22] Logo width/height ratio relative to QR code size
     */
    render: function(container, options) {
        let targetEl = (typeof container === 'string') ? document.getElementById(container) : container;
        if (!targetEl) return null;

        options = options || {};
        const text = options.text || '';
        const width = options.width || 80;
        const height = options.height || 80;
        const logoUrl = options.logoUrl || null;
        const logoRatio = options.logoRatio || 0.22;

        targetEl.innerHTML = '';
        targetEl.style.position = 'relative';
        targetEl.style.display = 'inline-block';
        targetEl.style.width = width + 'px';
        targetEl.style.height = height + 'px';

        const qrWrapper = document.createElement('div');
        qrWrapper.className = 'sgs-qr-code-wrapper';
        qrWrapper.style.width = '100%';
        qrWrapper.style.height = '100%';
        qrWrapper.style.background = '#ffffff';
        qrWrapper.style.padding = '2px';
        qrWrapper.style.boxSizing = 'border-box';
        targetEl.appendChild(qrWrapper);

        let qrInstance = null;
        if (typeof QRCode !== 'undefined') {
            qrInstance = new QRCode(qrWrapper, {
                text: text,
                width: width - 4,
                height: height - 4,
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        if (logoUrl) {
            const logoOverlay = document.createElement('div');
            logoOverlay.className = 'sgs-qr-logo-overlay';
            logoOverlay.style.position = 'absolute';
            logoOverlay.style.top = '50%';
            logoOverlay.style.left = '50%';
            logoOverlay.style.transform = 'translate(-50%, -50%)';
            logoOverlay.style.width = Math.round(width * logoRatio) + 'px';
            logoOverlay.style.height = Math.round(height * logoRatio) + 'px';
            logoOverlay.style.background = '#ffffff';
            logoOverlay.style.padding = '1px';
            logoOverlay.style.borderRadius = '2px';
            logoOverlay.style.display = 'flex';
            logoOverlay.style.alignItems = 'center';
            logoOverlay.style.justifyContent = 'center';
            logoOverlay.style.boxSizing = 'border-box';

            const img = document.createElement('img');
            img.src = logoUrl;
            img.style.maxWidth = '100%';
            img.style.maxHeight = '100%';
            img.style.objectFit = 'contain';

            logoOverlay.appendChild(img);
            targetEl.appendChild(logoOverlay);
        }

        return qrInstance;
    }
};
