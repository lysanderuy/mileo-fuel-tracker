(function () {
  'use strict';

  var SVG_UP   = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>';
  var SVG_DOWN = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>';
  var SVG_FLAT = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M5 12h14"/></svg>';

  function esc(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function fmtDate(dateStr) {
    var d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
  }

  // minDecimals: 0 for currency display without trailing zeros, 2 for precise amounts
  function fmtPeso(n, minDecimals) {
    var min = (minDecimals === undefined) ? 2 : minDecimals;
    return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: min, maximumFractionDigits: 2 });
  }

  function showToast(message, type) {
    var root = document.getElementById('mm-toast-root');
    if (!root) return;
    var toast = document.createElement('div');
    toast.className = 'ql-toast' + (type ? ' ' + type : '');
    toast.textContent = message;
    root.appendChild(toast);
    setTimeout(function () { toast.remove(); }, 5500);
  }

  // Returns { cls: 'green'|'yellow'|'red', icon: svgString }
  // Shows trend vs prior fill-up only. No absolute judgment.
  // First fill-up (no prior) returns neutral/flat.
  function effBadgeInfo(kml, priorKml) {
    if (priorKml === null || priorKml === undefined) {
      return { cls: 'yellow', icon: SVG_FLAT };
    }
    var pct = ((kml - priorKml) / priorKml) * 100;
    if (pct > 5)  return { cls: 'green',  icon: SVG_UP };
    if (pct < -5) return { cls: 'red',    icon: SVG_DOWN };
    return { cls: 'yellow', icon: SVG_FLAT };
  }

  window.MileoUtils = { esc: esc, fmtDate: fmtDate, fmtPeso: fmtPeso, showToast: showToast, effBadgeInfo: effBadgeInfo };
})();
