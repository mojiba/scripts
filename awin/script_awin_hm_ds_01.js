var AWIN = AWIN || { pluginsInitialized: !1 };
AWIN.publisher = {
  publisherId: 690313,
  siteName: "hardMOB",
  companyName: "hardMOB",
  primaryPromotionType: "Social Content"
};
(AWIN = AWIN || {}).initUrl = AWIN.initUrl || "https://www.dwin2.com/init.js";
Array.isArray || (Array.isArray = function (o) {
  return "[object Array]" === Object.prototype.toString.call(o);
});

(function (a) {
  var m = {},
    r = {
      events: {},
      on: function (o, e) {
        this.events[o] || (this.events[o] = []);
        this.events[o].push(e);
      },
      dispatch: function (o, e) {
        if (this.events[o])
          for (var c in this.events[o]) this.events[o][c](e);
      }
    };
  a.findMetaByName = function (o) {
    for (var e = {}, c = document.getElementsByTagName("meta"), a = 0; a < c.length; a++)
      c[a].name && c[a].content && 0 === c[a].name.indexOf(o) && (e[c[a].content] || (e[c[a].content] = []), e[c[a].content].push(c[a].name.split(":")[1]));
    return e;
  };
  a.findPluginInstructions = function (o) {
    var e = this.findMetaByName("awin");
    return e[o] || null;
  };
  a.appendScript = function (o, e, c) {
    var a = document.createElement("script");
    "function" == typeof c && (a.onload = a.onreadystatechange = function () {
      this.readyState && "loaded" !== this.readyState && "complete" !== this.readyState || (setTimeout(c, 1), a.onload = a.onreadystatechange = null);
    });
    a.setAttribute("src", o);
    e && Object.keys(e).forEach(function (o) { "id" === o ? a.id = e.id : a.setAttribute(o, e[o]); });
    document.body.appendChild(a);
  };
  a.contentReady = function (o) {
    "complete" === document.readyState || "interactive" === document.readyState ? setTimeout(o, 1) : document.addEventListener ? document.addEventListener("DOMContentLoaded", o) : document.attachEvent("onreadystatechange", function () { "complete" == document.readyState && o(); });
  };
  a.initPlugins = function () { a.contentReady(function (o) { a.appendScript(AWIN.initUrl); }); };
  a.runPlugins = function (o) {
    if (!a.pluginsInitialized)
      for (var e in a.pluginsInitialized = !0, o)
        if ("function" == typeof m[e]) try {
          var c = this.getPublisherConfig(e);
          a.mergeObject(c, o[e]);
          m[e](this.publisher, c, r);
        } catch (o) { }
  };
  a.mergeObject = function (o, e) {
    for (var c in e) o[c] && "object" == typeof o[c] && !Array.isArray(o[c]) ? a.mergeObject(o[c], e[c]) : o[c] = e[c];
  };
  a.getPublisherConfig = function (o) { return a.PluginConfig && a.PluginConfig[o] ? a.PluginConfig[o] : {}; };
  a.addPlugin = function (o, e) { return "function" == typeof e && (m[o] = e, !0); };
})(AWIN);

AWIN.PluginConfig = AWIN.PluginConfig || {};

// ========== referrer_transparency (mantido original, reduzido? Nenhum dado, só lógica) ==========
AWIN.PluginConfig.referrer_transparency = [];
AWIN.addPlugin("referrer_transparency", function (o, e, c) {
  var l = {
    _PARAM_NAME: "pmtr",
    _DATA_IGNORE_ATTRIBUTE: "data-referer-ignore",
    _AWIN_PATTERN: /https?\:\/\/(www\.)?(awin1|zenaps)\.com\/(awclick|cread|pclick)/,
    _AWIN_ENCODED_PATTERN: /https?%3a%2f%2f(?:www(?:\.|%2e))?(?:awin1|zenaps)(?:\.|%2e)com%2f(?:awclick|cread|pclick)/i,
    _getPageUrlWithoutQuery: function () { return (window.location && window.location.href || "").split("#")[0].split("?")[0]; },
    _setUrlParam: function (o, e, c) { var a = o.includes("?") ? "&" : "?"; return o.includes(e + "=") ? o.replace(new RegExp("([?&])" + e + "=[^&]*"), "$1" + e + "=" + c) : o + a + e + "=" + c; },
    _addParamToEncodedAwinUrl: function (o, e, c) {
      var a = o.indexOf("?");
      if (-1 === a) return null;
      for (var m = o.substring(0, a), r = o.substring(a + 1).split("&"), i = 0; i < r.length; i++) {
        var s = r[i].indexOf("=");
        if (-1 !== s) try {
          var t = decodeURIComponent(r[i].substring(s + 1));
          if (l._AWIN_PATTERN.test(t)) { var n = l._setUrlParam(t, e, c); return r[i] = r[i].substring(0, s + 1) + encodeURIComponent(n), m + "?" + r.join("&"); }
        } catch (o) { }
      }
      return null;
    },
    _addParamToUrl: function (o, e, c) {
      if ("string" != typeof o || !o) return o;
      var a = encodeURIComponent(c), c = o.match(l._AWIN_PATTERN);
      return c && 0 === c.index ? l._setUrlParam(o, e, a) : c ? o.substring(0, c.index) + l._setUrlParam(o.substring(c.index), e, a) : l._addParamToEncodedAwinUrl(o, e, a) || o;
    },
    _findParentAnchor: function (o) { return o ? "A" === o.tagName && void 0 !== o.href ? o : o.parentElement ? l._findParentAnchor(o.parentElement) : null : null; },
    _getAnchorFromEvent: function (o) { o = o && o.target || o; return o ? "A" === o.tagName && void 0 !== o.href ? o : l._findParentAnchor(o) : null; },
    _anchorShouldBeIgnored: function (o) { return null !== o.getAttribute(l._DATA_IGNORE_ATTRIBUTE); },
    _isAwinTrackingUrl: function (o) { return !!o && (l._AWIN_PATTERN.test(o) || l._AWIN_ENCODED_PATTERN.test(o)); },
    _doRefererClick: function (o) {
      var e = l._getAnchorFromEvent(o);
      e && e.href && !l._anchorShouldBeIgnored(e) && l._isAwinTrackingUrl(e.href) && (o = l._getPageUrlWithoutQuery(), o = l._addParamToUrl(e.getAttribute("href") || e.href, l._PARAM_NAME, o), e.setAttribute("href", o));
    },
    _addEventListener: function (o, e, c) { e.addEventListener ? e.addEventListener(o, c, !1) : e.attachEvent && e.attachEvent("on" + o, c); },
    _attachToAnchorIfEligible: function (o) { o.href && !l._anchorShouldBeIgnored(o) && l._isAwinTrackingUrl(o.href) && l._addEventListener("mousedown", o, l._doRefererClick); },
    _observeBodyMutations: function () {
      new MutationObserver(function (o) {
        for (var e = 0; e < o.length; e++)
          for (var c = o[e].addedNodes, a = 0; a < c.length; a++) {
            var m = c[a];
            if ("A" === m.tagName && l._attachToAnchorIfEligible(m), m.querySelectorAll)
              for (var r = m.querySelectorAll("a"), i = 0; i < r.length; i++) l._attachToAnchorIfEligible(r[i]);
          }
      }).observe(document.body, { childList: !0, subtree: !0 });
    },
    _init: function () {
      for (var o = document.getElementsByTagName("a"), e = 0; e < o.length; e++) l._attachToAnchorIfEligible(o[e]);
      function c() { document.body ? l._observeBodyMutations() : a < 5 && (a++, setTimeout(c, 200)); }
      var a = 0; c();
    }
  };
  return l._init(), l;
});

// ========== bouncelesstracking (appends reduzido apenas às lojas ativas) ==========
AWIN.PluginConfig.bouncelesstracking = {
  appends: {
    // Apenas os advertiserId que aparecem no CSV ativo (alguns não têm appends mapeados no original, mantemos os que existem)
    "17629": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17729": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17762": "origem=zanox&utm_source=zanox&utm_medium=cpa&utm_term=!!!affid!!!&utm_campaign=!!!clickref!!!&aw_affid=!!!id!!!",
    "17790": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17801": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17806": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17809": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17818": "utm_source=awin&utm_campaign=afiliados&utm_term=!!!affid!!!&utm_medium=!!!clickref!!!&aw_affid=!!!id!!!",
    "17824": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17837": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17854": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17858": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17870": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17874": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "17919": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "18120": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "18878": "utm_source=awin&utm_medium=affiliate_!!!affid!!!&utm_campaign=!!!clickref!!!&awaid=!!!id!!!&aw_affid=!!!id!!!",
    "19296": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "19672": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "19673": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "19675": "utm_medium=afiliados&utm_source=awin&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "22031": "utm_source=affiliate&utm_medium=cpa&utm_campaign=br_Affiliate_acq_ona_afm__all_b2c_awin_affiliatelink________&aw_affid=!!!id!!!",
    "23385": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "23524": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "23659": "utm_source=awin&utm_campaign=!!!sitenamechar!!!&utm_medium=affiliate&utm_id=!!!clickref!!!&utm_term=!!!id!!!&aw_affid=!!!id!!!",
    "23993": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "24534": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "24620": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "25279": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "25539": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "26113": "utm_source=awin&utm_medium=cpa&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "28165": "utm_source=awin&utm_campaign=afiliados&utm_term=!!!affid!!!&utm_medium=!!!clickref!!!&aw_affid=!!!id!!!",
    "28773": "utm_source=awin&utm_campaign=!!!companyname!!!&utm_term=!!!affid!!!_!!!clickref!!!&utm_medium=afiliados&aw_affid=!!!id!!!",
    "28775": "utm_source=awin&utm_campaign=!!!companyname!!!&utm_term=!!!affid!!!_!!!clickref!!!&utm_medium=afiliados&aw_affid=!!!id!!!",
    "29407": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "30275": "network=aw&utm_source=Affiliate&utm_medium=Awin&utm_campaign=!!!affid!!!_!!!companyname!!!&utm_content=!!!clickref!!!&aw_affid=!!!id!!!",
    "30511": "utm_source=awin&utm_campaign=!!!affid!!!_afiliados_!!!clickref!!!&aw_affid=!!!id!!!",
    "30599": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "30615": "utm_source=awin&utm_campaign=!!!affid!!!_afiliados_!!!clickref!!!&aw_affid=!!!id!!!",
    "31355": "utm_source=awin&utm_medium=afiliados&utm_id=!!!affid!!!_!!!clickref!!!&aw_affid=!!!id!!!",
    "32675": "utm_medium=AFF&utm_source=AWN_COM&utm_campaign=!!!companyname!!!&utm_campaigncode=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "32843": "utm_source=awin&utm_medium=afiliados&utm_id=!!!affid!!!&utm_campaign=alp_gol_varejo-nac_awin_conv_vend_always-on_generico_v1_dnmc_dnmc_geral_glv02614al&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "36382": "utm_source=awin&utm_medium=afiliados&utm_id=!!!affid!!!_!!!clickref!!!&aw_affid=!!!id!!!&utm_content=!!!affid!!!_!!!clickref!!!&utm_campaign=!!!affid!!!_!!!clickref!!!",
    "38988": "utm_source=awin&utm_medium=afiliados&utm_id=!!!affid!!!_!!!clickref!!!&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "47533": "utm_source=awin&utm_medium=aff_xml&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=sejam_bem-vindos_awin&utm_content=cat_sub_data&canal=ca_11661&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "48557": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "50557": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&aw_affid=!!!id!!!",
    "51271": "utm_source=awin&utm_medium=!!!affid!!!&utm_id=!!!clickref!!!&aw_affid=!!!id!!!",
    "51277": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "62481": "utm_source=awin&utm_medium=affiliates&utm_id=!!!clickref!!!&sv1=affiliate&utm_campaign=!!!affid!!!&aw_affid=!!!id!!!",
    "64086": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "74062": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "77140": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "78292": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "79926": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "79974": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "80302": "utm_source=awin&utm_medium=afiliados&utm_campaign=SCHUTZ_MIRUM_ECOM_AFILIADOS_CONVERSAO_AWIN_VENDAS_CONVERSAO_INSTITUCIONAL_BRAND&utm_id=!!!affid!!!_!!!clickref!!!&aw_affid=!!!id!!!",
    "83225": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "83253": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "84619": "utm_source=awin&utm_campaign=!!!id!!!&sv1=affiliate&sv_campaign_id=affiliate&utm_term=!!!affid!!!_!!!clickref!!!&aw_affid=!!!id!!!",
    "105615": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "108626": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "112634": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "112756": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "114398": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&partner=awin&sv1=affiliate&sv_campaign_id=!!!id!!!&utm_medium=referral",
    "115463": "source=aw&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "115519": "source=aw&utm_source=awin&utm_medium=cpc&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "115521": "source=aw&utm_source=awin&utm_medium=cpc&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "117985": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=!!!promotype!!!&sv1=affiliate&sv_campaign_id=!!!id!!!&utm_medium=affiliate&utm_content=!!!affid!!!",
    "117987": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=!!!promotype!!!&sv1=affiliate&sv_campaign_id=!!!id!!!&utm_medium=affiliate&utm_content=!!!affid!!!",
    "118923": "utm_source=awin&utm_campaign=!!!id!!!&sv1=affiliate&sv_campaign_id=affiliate&utm_term=!!!affid!!!_!!!clickref!!!&aw_affid=!!!id!!!",
    "119883": "utm_source=awin&utm_medium=afiliados&utm_campaign=!!!affid!!!&utm_term=!!!clickref!!!&aw_affid=!!!id!!!",
    "120990": "source=aw&utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!",
    "124204": "utm_source=awin&utm_id=!!!affid!!!_!!!clickref!!!&utm_campaign=affiliate&sv1=affiliate&sv_campaign_id=!!!id!!!"
  }
};

AWIN.addPlugin("bouncelesstracking", function (r, c, a) {
  var o, n = {};
  function s(o, e, c, a, m, r) { return h((r = h(h(e, o), h(a, r))) << m | r >>> 32 - m, c); }
  function w(o, e, c, a, m, r, i) { return s(e & c | ~e & a, o, e, m, r, i); }
  function u(o, e, c, a, m, r, i) { return s(e & a | c & ~a, o, e, m, r, i); }
  function d(o, e, c, a, m, r, i) { return s(e ^ c ^ a, o, e, m, r, i); }
  function p(o, e, c, a, m, r, i) { return s(c ^ (e | ~a), o, e, m, r, i); }
  function h(o, e) { var c = (65535 & o) + (65535 & e); return (o >> 16) + (e >> 16) + (c >> 16) << 16 | 65535 & c; }
  n._DATA_ATTRIBUTE_NAME = "data-blt";
  n._REFERRER_PARAM_NAME = "extr";
  n._MODE_PARAM_NAME = "bltmode";
  n._AWC_PARAM_NAME = "bawc";
  n._HASH_PARAM_NAME = "blthash";
  n._canUseBeacon = function () { return "function" == typeof navigator.sendBeacon; };
  n._isInternetExplorer = -1 < window.navigator.userAgent.indexOf("MSIE ") || -1 < window.navigator.userAgent.indexOf("Trident/7.");
  n._beaconEndpoint = c.beaconEndpoint || "https://www.zenaps.com/blt";
  n._beaconEndpointResource = c.beaconEndpointResource || "favicon.ico";
  n._analyticalEndpoint = c.analyticalEndpoint || "https://ri1697oh1m.execute-api.eu-central-1.amazonaws.com/v1/blt";
  n._blockers = c.blockers || ["dailygo", "monotote", "roeye"];
  n._affiliateId = r.publisherId;
  n._config = c;
  n._appends = c.appends;
  n._otherPluginsAllowBlt = function () { for (var o = 0; o < n._blockers.length; o++) { var e = n._blockers[o]; if (AWIN.PluginConfig[e]) return !1; } return !0; };
  n._browserAllowsBlt = function () { return n._canUseBeacon() && !n._isInternetExplorer; };
  n._deeplinkParameterAllowsBlt = function (o) { return !(!o.p || -1 === o.p.indexOf("http")) || !(!o.ued || -1 === o.ued.indexOf("http")); };
  n._userAllowsBlt = function (o) { return !o || "0" !== o; };
  n._advertiserAllowsBlt = function (o) { o = parseInt(o); return void 0 !== n._config.advertisers && null !== n._config.advertisers && -1 !== n._config.advertisers.indexOf(o); };
  n._cloneObject = function (o) { var e, c = {}; for (e in o) o.hasOwnProperty(e) && (c[e] = o[e]); return c; };
  n._removeQueryStringFromUrl = function (o) { return (o || "").split("?")[0]; };
  n._getAdvertiserIdFromQueryParameters = function (o) { return o.awinmid || o.mid || o.v || void 0; };
  n._getQueryParams = function (o) { for (var e, c = {}, a = /[?&]?([^=]+)=(\[\[.*\]\]|[^&]*)/g; e = a.exec(o);) c[e[1]] = e[2]; return c; };
  n._addParametersToUrl = function (o, e) { if ("string" == typeof o) { var c = o.split("?"), o = Object.keys(e).map(function (o) { return o + "=" + n._addBracketsToParameterValueAndEncodeIfNeeded(e[o], o); }); return 1 < c.length && o.push(c[1]), c[1] = o.join("&"), c.join("?"); } };
  n._addParameterToUrl = function (o, e, c) { if ("string" == typeof o) { var a = new Array(1); return a[e] = c, n._addParametersToUrl(o, a); } };
  n._addBracketsToParameterValueAndEncodeIfNeeded = function (o, e) { return o.toString().match(/^http/g) ? (o.toString().match(/^https?\:/g) && (o = encodeURIComponent(o)), "ued" === e ? o : "[[" + o + "]]") : o; };
  n._buildAwc = function (o) { if (o) { var e = Date.now() / 1e3 | 0; return o + "_" + e + "_" + n._md5(Math.random().toString(36) + e); } };
  n._buildBltHash = function (o) { return n._md5(o + Math.random().toString(36) + Date.now()); };
  n._md5 = function (o) { return function (o) { for (var e, c = "0123456789ABCDEF", a = "", m = 0; m < o.length; m++) e = o.charCodeAt(m), a += c.charAt(e >>> 4 & 15) + c.charAt(15 & e); return a; }(function (o) { for (var e = "", c = 0; c < 32 * o.length; c += 8) e += String.fromCharCode(o[c >> 5] >>> c % 32 & 255); return e; }(function (o, e) { o[e >> 5] |= 128 << e % 32, o[14 + (e + 64 >>> 9 << 4)] = e; for (var c = 1732584193, a = -271733879, m = -1732584194, r = 271733878, i = 0; i < o.length; i += 16) { var s = c, t = a, n = m, l = r; a = p(a = p(a = p(a = p(a = d(a = d(a = d(a = d(a = u(a = u(a = u(a = u(a = w(a = w(a = w(a = w(a, m = w(m, r = w(r, c = w(c, a, m, r, o[i + 0], 7, -680876936), a, m, o[i + 1], 12, -389564586), c, a, o[i + 2], 17, 606105819), r, c, o[i + 3], 22, -1044525330), m = w(m, r = w(r, c = w(c, a, m, r, o[i + 4], 7, -176418897), a, m, o[i + 5], 12, 1200080426), c, a, o[i + 6], 17, -1473231341), r, c, o[i + 7], 22, -45705983), m = w(m, r = w(r, c = w(c, a, m, r, o[i + 8], 7, 1770035416), a, m, o[i + 9], 12, -1958414417), c, a, o[i + 10], 17, -42063), r, c, o[i + 11], 22, -1990404162), m = w(m, r = w(r, c = w(c, a, m, r, o[i + 12], 7, 1804603682), a, m, o[i + 13], 12, -40341101), c, a, o[i + 14], 17, -1502002290), r, c, o[i + 15], 22, 1236535329), m = u(m, r = u(r, c = u(c, a, m, r, o[i + 1], 5, -165796510), a, m, o[i + 6], 9, -1069501632), c, a, o[i + 11], 14, 643717713), r, c, o[i + 0], 20, -373897302), m = u(m, r = u(r, c = u(c, a, m, r, o[i + 5], 5, -701558691), a, m, o[i + 10], 9, 38016083), c, a, o[i + 15], 14, -660478335), r, c, o[i + 4], 20, -405537848), m = u(m, r = u(r, c = u(c, a, m, r, o[i + 9], 5, 568446438), a, m, o[i + 14], 9, -1019803690), c, a, o[i + 3], 14, -187363961), r, c, o[i + 8], 20, 1163531501), m = u(m, r = u(r, c = u(c, a, m, r, o[i + 13], 5, -1444681467), a, m, o[i + 2], 9, -51403784), c, a, o[i + 7], 14, 1735328473), r, c, o[i + 12], 20, -1926607734), m = d(m, r = d(r, c = d(c, a, m, r, o[i + 5], 4, -378558), a, m, o[i + 8], 11, -2022574463), c, a, o[i + 11], 16, 1839030562), r, c, o[i + 14], 23, -35309556), m = d(m, r = d(r, c = d(c, a, m, r, o[i + 1], 4, -1530992060), a, m, o[i + 4], 11, 1272893353), c, a, o[i + 7], 16, -155497632), r, c, o[i + 10], 23, -1094730640), m = d(m, r = d(r, c = d(c, a, m, r, o[i + 13], 4, 681279174), a, m, o[i + 0], 11, -358537222), c, a, o[i + 3], 16, -722521979), r, c, o[i + 6], 23, 76029189), m = d(m, r = d(r, c = d(c, a, m, r, o[i + 9], 4, -640364487), a, m, o[i + 12], 11, -421815835), c, a, o[i + 15], 16, 530742520), r, c, o[i + 2], 23, -995338651), m = p(m, r = p(r, c = p(c, a, m, r, o[i + 0], 6, -198630844), a, m, o[i + 7], 10, 1126891415), c, a, o[i + 14], 15, -1416354905), r, c, o[i + 5], 21, -57434055), m = p(m, r = p(r, c = p(c, a, m, r, o[i + 12], 6, 1700485571), a, m, o[i + 3], 10, -1894986606), c, a, o[i + 10], 15, -1051523), r, c, o[i + 1], 21, -2054922799), m = p(m, r = p(r, c = p(c, a, m, r, o[i + 8], 6, 1873313359), a, m, o[i + 15], 10, -30611744), c, a, o[i + 6], 15, -1560198380), r, c, o[i + 13], 21, 1309151649), m = p(m, r = p(r, c = p(c, a, m, r, o[i + 4], 6, -145523070), a, m, o[i + 11], 10, -1120210379), c, a, o[i + 2], 15, 718787259), r, c, o[i + 9], 21, -343485551), c = h(c, s), a = h(a, t), m = h(m, n), r = h(r, l); } return [c, a, m, r]; }(function (o) { for (var e = Array(o.length >> 2), c = 0; c < e.length; c++) e[c] = 0; for (c = 0; c < 8 * o.length; c += 8) e[c >> 5] |= (255 & o.charCodeAt(c / 8)) << c % 32; return e; }(o), 8 * o.length)))).toLowerCase(); };
  n._isAwinTrackingUrl = function (o) { return !!o && !!o.match(/^https?\:\/\/(www\.)?(awin1|zenaps)\.com\/(awclick|cread)/); };
  n._anchorHasBltDataAttribute = function (o) { return "string" == typeof o.getAttribute(n._DATA_ATTRIBUTE_NAME); };
  n._buildBeaconUrl = function (o) { var e = n._analyticalEndpoint; return n._advertiserAllowsBlt(o.advertiserId) && (e = n._beaconEndpoint), e = n._addParametersToUrl(e, o.parameters); };
  n._addRefererUrl = function (o) { var e = n._REFERRER_PARAM_NAME + "=" + n._addBracketsToParameterValueAndEncodeIfNeeded(n._removeQueryStringFromUrl(window.location.href)); return -1 === o.indexOf(e) && (o += "&" + e), o; };
  n._sanitiseTrackingUrl = function (o) { var e = (o = o.replace("http://", "https://")).indexOf("www."); return o = -1 === e || 15 < e ? o.replace("//", "//www.") : o; };
  n._addEndpointPixel = function (o, e) { var c = document.getElementsByTagName("body")[0]; c && (o = o.replace("blt", e) + "?vendor=[[" + escape(navigator.vendor) + "]]", (e = document.createElement("img")).setAttribute("width", "0px"), e.setAttribute("height", "0px"), e.setAttribute("src", o), c.appendChild(e)); };
  n._addEventListener = function (o, e, c) { e.addEventListener ? e.addEventListener(o, c, !1) : e.attachEvent && e.attachEvent("on" + o, c); };
  n._addOrUpdatePingAttribute = function (o, e) { var c = o.getAttribute("ping"), a = e, m = []; if (c) { for (var r = c.split(" "), i = 0; i < r.length; i++) { var s = r[i]; 0 < s.length && -1 === s.indexOf(n._beaconEndpoint) && -1 === s.indexOf(n._analyticalEndpoint) && m.push(s); } m.push(e), a = m.join(" "), o.setAttribute("ping", a); } else o.setAttribute("ping", a); };
  n._setBltDataAttributeWithEncodedObject = function (o, e) { e = JSON.stringify(e), e = encodeURIComponent(e); o.setAttribute(n._DATA_ATTRIBUTE_NAME, e); };
  n._findParentAnchor = function (o) { return "A" === o.tagName && void 0 !== o.href ? o : n._findParentAnchor(o.parentElement); };
  n._createNewBouncelessTrackingConfig = function (o, e) { var c = o.getAttribute("href"), a = c.substring(0, c.indexOf("?")), m = n._cloneObject(e), r = n._getAdvertiserIdFromQueryParameters(e), o = n._buildAwc(r), c = n._buildBltHash(c); return m[n._AWC_PARAM_NAME] = o, m[n._REFERRER_PARAM_NAME] = n._removeQueryStringFromUrl(window.location.href), m[n._HASH_PARAM_NAME] = c, e[n._HASH_PARAM_NAME] = c, m.vendor = "[[" + escape(navigator.vendor) + "]]", { advertiserId: r, awc: o, hrefUrl: a = n._advertiserAllowsBlt(r) ? (r = e.p || e.ued, -1 < (r = (a = decodeURIComponent(r).replace(/[\[\]]*/g, "")).indexOf("%3A%2F%2F")) && r < 20 && (a = decodeURIComponent(a)), n._addParameterToUrl(a, "awc", o)) : (a = n._addParametersToUrl(a, e), a = n._addRefererUrl(a), n._sanitiseTrackingUrl(a)), parameters: m }; };
  n._getBounceLessConfigFromAttribute = function (o) { if (n._anchorHasBltDataAttribute(o)) { var e = decodeURIComponent(o.getAttribute(n._DATA_ATTRIBUTE_NAME)), c = JSON.parse(e), a = n._getAdvertiserIdFromQueryParameters(c.parameters), e = n._buildAwc(a), o = n._buildBltHash(o.href); return c.parameters[n._AWC_PARAM_NAME] = e, c.parameters[n._HASH_PARAM_NAME] = o, { advertiserId: a, awc: e, hrefUrl: c.hrefUrl.replace(/awc\=\d{1,6}\_\d{10}\_[\d\w]*/, "awc=" + e).replace(/blthash\=[\d\w]{32}/, n._HASH_PARAM_NAME + "=" + o), parameters: c.parameters }; } };
  n._doBounceLessClick = function (o) { var e, c = n._findParentAnchor(o.target), a = c.href, m = n._getBounceLessConfigFromAttribute(c); m || (o = a.substring(a.indexOf("?")), o = n._getQueryParams(o), n._deeplinkParameterAllowsBlt(o) && n._browserAllowsBlt() && n._userAllowsBlt(o.cons) && (m = n._createNewBouncelessTrackingConfig(c, o))), m ? n._browserAllowsBlt() && (e = m.hrefUrl, n._appends && n._appends[m.advertiserId] && (e = n._addAppendsToHref(e, n._appends[m.advertiserId], m, r)), m.parameters[n._MODE_PARAM_NAME] = "beacon", navigator.sendBeacon(n._buildBeaconUrl(m)), m.parameters[n._MODE_PARAM_NAME] = "ping", n._addOrUpdatePingAttribute(c, n._buildBeaconUrl(m)), n._setBltDataAttributeWithEncodedObject(c, m)) : (a = n._addRefererUrl(a), e = n._sanitiseTrackingUrl(a)), c.setAttribute("href", e); };
  n._addAppendsToHref = function (o, e, c, a) { for (var m = new RegExp(/!{3}(\w+)!{3}/g), r = e.replace(/^[?&]*/g, "").replace(/[?&]*$/g, ""), i = new Date, s = o.split(/^(.*?)\?(.*)/), o = s[1], s = s[2]; null !== (matcher = m.exec(e));) { var t = ""; switch (matcher[1]) { case "sitename": t = n._urlEncode(a.siteName); break; case "sitenamecm": t = n._urlEncode(a.siteName, !0); break; case "sitenamechar": t = n._stripNonChars(a.siteName); break; case "affiliatedomain": t = n._getDomainName(c.parameters[n._REFERRER_PARAM_NAME], a.siteName); break; case "promotype": t = n._urlEncode(a.primaryPromotionType); break; case "promotypecm": t = n._urlEncode(a.primaryPromotionType, !0); break; case "id": case "add": case "aid": case "affid": t = a.publisherId; break; case "dateday": t = n._getCurrentDay(i); break; case "timestamp": t = Math.floor(i / 1e3); break; case "linkid": t = n._getLinkId(c.parameters); break; case "clickref": t = n._urlEncode(c.parameters.clickref); break; case "awc": case "awv": t = c.awc; break; case "companyname": t = n._urlEncode(a.companyName); break; default: t = "unknown"; } r = r.replace(matcher[0], t || ""); } return o + "?" + (r ? r + "&" : "") + s; };
  n._getDomainName = function (o, e) { return o ? n._urlEncode(o.replace("http://", "").replace("https://", "")) : n._urlEncode(e); };
  n._getLinkId = function (o) { return o.linkid || o.s || 0; };
  n._stripNonChars = function (o) { if (o) return o.replace(/[^A-Za-z0-9_]+/g, ""); };
  n._getCurrentDay = function (o) { return [o.getFullYear(), ("0" + (o.getMonth() + 1)).slice(-2), ("0" + o.getDate()).slice(-2)].join(""); };
  n._urlEncode = function (o, e) { if (void 0 === o) return o; for (var c = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F"], a = o.length, m = "", r = 0, i = 0; i < o.length; i++) { var s = o.charAt(i); /^[A-Za-z0-9]+$/.test(s) || (e || "-" != s && "." != s && "_" != s) && (r < i && (m += o.substring(r, i)), r = i + 1, " " != s || e ? n._toUTF8Array(s).forEach(function (o, e) { m += "%", m += c[o >> 4 & 15] + c[15 & o]; }) : m += "+"); } return 0 === m.length ? o : (r < a && (m += o.substring(r, a)), m); };
  n._toUTF8Array = function (o) { for (var e = [], c = 0; c < o.length; c++) { var a = o.charCodeAt(c); a < 128 ? e.push(a) : a < 2048 ? e.push(192 | a >> 6, 128 | 63 & a) : a < 55296 || 57344 <= a ? e.push(224 | a >> 12, 128 | a >> 6 & 63, 128 | 63 & a) : (c++, a = 65536 + ((1023 & a) << 10 | 1023 & o.charCodeAt(c)), e.push(240 | a >> 18, 128 | a >> 12 & 63, 128 | a >> 6 & 63, 128 | 63 & a)); } return e; };
  n._init = function (o) { for (var e = 0; e < o.length; e++) n._isAwinTrackingUrl(o[e].href) && (n._addEventListener("mousedown", o[e], n._doBounceLessClick), o[e].classList.add("noskimlinks")); (/(OS 11_|OS 12_).*like Mac OS X.*/.test(navigator.userAgent) || /(Macintosh|iPhone);.*Mac OS X.*Version\/(11|12).* Safari/.test(navigator.userAgent)) && n._addEndpointPixel(n._beaconEndpoint, n._beaconEndpointResource); a.on("link.click", n._doBounceLessClick), c && c.unitTestExport && (AWIN.BouncelessTracking = n); };
  n._otherPluginsAllowBlt() && (o = document.getElementsByTagName("a"), n._init(o)), n;
});

// ========== convertalink (apenas domínios das lojas ativas) ==========
AWIN.PluginConfig.convertalink = {
  domains: {
    "basico.com": 115519,
    "kaspersky.com.br": 22031,
    "cafelor.com.br": 19672,
    "ze.delivery": 112634,
    "trocafy.com.br": 51277,
    "loja.electrolux.com.br": 17858,
    "gigantec.com.br": 115463,
    "loja.panasonic.com.br": 78382,
    "stanley1913.com.br": 30599,
    "casasbahia.com.br": 17629,
    "colchoesemma.com.br": 23385,
    "carrefour.com.br": 17665,
    "luuna.com.br": 30275,
    "oqvestir.com.br": 117985,
    "eotica.com.br": 17854,
    "extra.com.br": 17874,
    "continentalbrasil.com.br": 19675,
    "benoit.com.br": 79974,
    "lebiscuit.com.br": 24620,
    "descorcha.com": 114398,
    "schutz.com.br": 80302,
    "eudora.com.br": 17837,
    "consul.com.br": 17639,
    "dafiti.com.br": 17697,
    "lacoste.com.br": 112756,
    "lojasrenner.com.br": 17801,
    "adidas.com.br": 79926,
    "kabum.com.br": 17729,
    "mizuno.com.br": 51271,
    "madeiramadeira.com.br": 17762,
    "arno.com.br": 108626,
    "boticario.com.br": 17659,
    "araujo.com.br": 17919,
    "shop.samsung.com.br": 25539,
    "youcom.com.br": 17568,
    "camicado.com.br": 17790,
    "paguemenos.com.br": 64086,
    "camainbox.com.br": 38988,
    "polishop.com.br": 26113,
    "posthaus.com.br": 17634,
    "artwalk.com.br": 28773,
    "spicy.com.br": 30615,
    "mobly.com.br": 17777,
    "decanter.com.br": 50557,
    "lego.com.br": 30511,
    "cursospm3.com.br": 74062,
    "avon.com.br": 23993,
    "havaianas.com.br": 119883,
    "nike.com.br": 17652,
    "compracerta.com.br": 17824,
    "wine.com.br": 17770,
    "iplace.com.br": 31355,
    "belezanaweb.com.br": 29407,
    "shopclub.com.br": 17668,
    "olympikus.com.br": 17698,
    "pilao.com.br": 19673,
    "br-store.acer.com": 18878,
    "efacil.com.br": 47533,
    "ibbl.com.br": 77140,
    "drjones.com.br": 84619,
    "brastemp.com.br": 17772,
    "pontofrio.com.br": 17621,
    "asics.com.br": 23659,
    "cobasi.com.br": 17870,
    "timecenter.com.br": 28165,
    "centauro.com.br": 17806,
    "booking.com": 18120,
    "darklabsuplementos.com.br": 120990,
    "brinox.com.br": 25279,
    "sejasocio.samsclub.com.br": 124204,
    "basicamente.com": 115521,
    "webcontinental.com.br": 17743,
    "dufrio.com.br": 23524,
    "malwee.com.br": 83225,
    "viainox.com": 83253,
    "motorola.com.br": 24534,
    "elements.com.br": 48557,
    "leveros.com.br": 105615,
    "webfones.com.br": 78292,
    "trussprofessional.com.br": 118923,
    "shop2gether.com.br": 117987,
    "belezanawebpro.com.br": 62481,
    "vivara.com.br": 17662,
    "nescafe-dolcegusto.com.br": 17797,
    "evino.com.br": 17818,
    "fastshop.com.br": 17590,
    "voegol.com.br": 32843,
    "cea.com.br": 17648,
    "br.puma.com": 32675,
    "decathlon.com.br": 19296,
    "authenticfeet.com.br": 28775,
    "tokstok.com.br": 36382,
    "kitchenaid.com.br": 17809
  }
};

AWIN.addPlugin("convertalink", function (m, o, c) {
  "undefined" != typeof Element && (Element.prototype.matches || (Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector), Element.prototype.closest || (Element.prototype.closest = function (o) { var e = this; do { if (e.matches(o)) return e; } while (null !== (e = e.parentElement || e.parentNode) && 1 === e.nodeType); return null; }));
  String.prototype.trim || (String.prototype.trim = function () { return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, ""); });
  var r = {};
  if (!o.domains || 0 == o.domains.length) return !1;
  var i = o.domains, s = "http://convertalinktest.awin.com";
  o.pluginTestUrl && (s = o.pluginTestUrl);
  r.getAdvertiserIdForUrl = function (o) {
    var e = o.replace("http://", "").replace("https://", "").split(/[/?#]/)[0];
    if (e === (window && window.location ? window.location.hostname : "")) return !1;
    var c, a = "[a-zA-Z0-9\\-]+\\.", m = new RegExp("^" + a + "$");
    for (c in i) if (-1 === (r = c.indexOf("*."))) { if (e === c) return i[c]; } else if (0 === r) { if (e.substr(e.indexOf(".") + 1) === c.substr(c.indexOf("*.") + "*.".length) && e.substr(0, e.indexOf(".") + 1).match(m)) return i[c]; } else { var r = new RegExp("^" + c.split("*.").join(a) + "$"); if (e.match(r)) return i[c]; }
    return !1;
  };
  r.getLinkClickCallback = function (o, e) { var c = e.link, a = c.getAttribute("href").trim(), m = "_blank"; o.preventDefault ? o.preventDefault() : o.returnValue = !1, 0 === o.button && (m = c.getAttribute("target")), a.replace("http://", "").replace("https://", "").split(/[/?#]/)[0] == s.replace("http://", "").replace("https://", "").split(/[/?#]/)[0] ? r.clickLink(r.buildTrackingLink(r.buildTestingUrl(), m), !0) : (c = r.getClickRef(c), r.clickLink(r.buildTrackingLink(r.buildTrackingUrl(a, e.advertiserId, c), m))); };
  r.getClickRef = function (o) { var e, c, a, m = ["", 2, 3, 4, 5, 6], r = []; for (a in m) null != (c = o.getAttribute("data-" + (e = "clickref" + m[a]))) && 0 < c.length ? r.push(e + "=" + encodeURIComponent(c)) : "" === m[a] && r.push(e + "=convert-a-link"); return r; };
  r.clickLink = function (o, e) { document.body.appendChild(o), e || c.dispatch("link.click", { plugin: "convertalink", target: o }), o.click(), document.body.removeChild(o); };
  r.buildTrackingLink = function (o, e) { var c = document.createElement("a"); return c.setAttribute("href", o), "string" == typeof e && c.setAttribute("target", e), c.setAttribute("data-awinignore", "true"), c.setAttribute("style", "display:none"), c; };
  r.buildTrackingUrl = function (o, e, c) { var a = (window.location && window.location.href || "").split("#")[0].split("?")[0]; return "https://www.awin1.com/cread.php?awinmid=" + e + "&awinaffid=" + m.publisherId + "&pmtr=" + encodeURIComponent(a) + "&" + c.join("&") + "&platform=cl&ued=" + encodeURIComponent(o); };
  r.buildTestingUrl = function () { return s + "/success-page.html"; };
  r.validateLink = function (o) { var e = r.scanForAnchor(o); if (!e) return !1; if ("A" !== e.tagName) return !1; if (null !== e.getAttributeNode("data-awinignore")) return !1; if (null === e.getAttributeNode("href")) return !1; o = e.getAttributeNode("href").value; if ("string" != typeof o) return !1; o = this.getAdvertiserIdForUrl(o.trim()); return !1 !== o && (null === e.getAttributeNode("data-blt") && { link: e, advertiserId: o }); };
  r.callbackStop = function (o) { r.validateLink(o.target) && o.preventDefault(); };
  r.callbackGo = function (o) { o = o || window.event; var e = r.validateLink(o.target || o.srcElement); !1 !== e && r.getLinkClickCallback(o, e); };
  r.scanForAnchor = function (o) { if (o.closest) return o.closest("a"); for (; o;) { if ("A" === o.tagName) return o; o = o.parentNode; } return null; };
  r.attachEvents = function () { document.addEventListener ? (document.addEventListener("click", r.callbackStop, !1), document.addEventListener("mouseup", r.callbackGo, !1)) : document.attachEvent("onclick", r.callbackGo); };
  r.init = function () { this.attachEvents(); };
  r.init(), r;
});

AWIN.initPlugins();
