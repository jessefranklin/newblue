!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a(jQuery)}(function(a){function b(a){if(a.minTime&&(a.minTime=s(a.minTime)),a.maxTime&&(a.maxTime=s(a.maxTime)),a.durationTime&&"function"!=typeof a.durationTime&&(a.durationTime=s(a.durationTime)),a.disableTimeRanges.length>0){for(var b in a.disableTimeRanges)a.disableTimeRanges[b]=[s(a.disableTimeRanges[b][0]),s(a.disableTimeRanges[b][1])];a.disableTimeRanges=a.disableTimeRanges.sort(function(a,b){return a[0]-b[0]})}return a}function c(b){var c=b.data("timepicker-settings"),d=b.data("timepicker-list");d&&d.length&&(d.remove(),b.data("timepicker-list",!1)),d=a("<ul />",{"class":"ui-timepicker-list"});var e=a("<div />",{"class":"ui-timepicker-wrapper",tabindex:-1});e.css({display:"none",position:"absolute"}).append(d),c.className&&e.addClass(c.className),null===c.minTime&&null===c.durationTime||!c.showDuration||e.addClass("ui-timepicker-with-duration");var f=c.minTime;"function"==typeof c.durationTime?f=s(c.durationTime()):null!==c.durationTime&&(f=c.durationTime);var h=null!==c.minTime?c.minTime:0,j=null!==c.maxTime?c.maxTime:h+v-1;h>=j&&(j+=v);for(var k=c.disableTimeRanges,l=0,m=k.length,n=h;j>=n;n+=60*30){var o=n%v,t=a("<li />");if(t.data("time",o),t.text(r(o,c.timeFormat)),(null!==c.minTime||null!==c.durationTime)&&c.showDuration){var u=a("<span />");u.addClass("ui-timepicker-duration"),u.text(" ("+q(n-f)+")"),t.append(u)}m>l&&(o>=k[l][1]&&(l+=1),k[l]&&o>=k[l][0]&&o<k[l][1]&&t.addClass("ui-timepicker-disabled")),d.append(t)}e.data("timepicker-input",b),b.data("timepicker-list",e);var w=c.appendTo;"string"==typeof w?w=a(w):"function"==typeof w&&(w=w(b)),w.append(e),i(b,d),d.on("click","li",function(){b.off("focus.timepicker"),b.on("focus.timepicker-ie-hack",function(){b.off("focus.timepicker-ie-hack"),b.on("focus.timepicker",y.show)}),g(b)||b[0].focus(),d.find("li").removeClass("ui-timepicker-selected"),a(this).addClass("ui-timepicker-selected"),p(b)&&(b.trigger("hideTimepicker"),e.hide())})}function d(){return new Date(1970,1,1,0,0,0)}function e(b){"ontouchstart"in document?a("body").on("touchstart.ui-timepicker",f):(a("body").on("mousedown.ui-timepicker",f),b.closeOnWindowScroll&&a(window).on("scroll.ui-timepicker",f))}function f(b){var c=a(b.target),d=c.closest(".ui-timepicker-input");0===d.length&&0===c.closest(".ui-timepicker-wrapper").length&&(y.hide(),a("body").unbind(".ui-timepicker"),a(window).unbind(".ui-timepicker"))}function g(a){var b=a.data("timepicker-settings");return(window.navigator.msMaxTouchPoints||"ontouchstart"in document)&&b.disableTouchKeyboard}function h(b,c,d){if(!d&&0!==d)return!1;var e=b.data("timepicker-settings"),f=!1,g=30*e.step;return c.find("li").each(function(b,c){var e=a(c),h=e.data("time")-d;return Math.abs(h)<g||h==g?(f=e,!1):void 0}),f}function i(a,b){b.find("li").removeClass("ui-timepicker-selected");var c=s(k(a));if(null!==c){var d=h(a,b,c);if(d){var e=d.offset().top-b.offset().top;(e+d.outerHeight()>b.outerHeight()||0>e)&&b.scrollTop(b.scrollTop()+d.position().top-d.outerHeight()),d.addClass("ui-timepicker-selected")}}}function j(){if(""!==this.value){var b=a(this),c=b.data("timepicker-list");if(!c||!c.is(":visible")){var d=s(this.value);if(null===d)return b.trigger("timeFormatError"),void 0;var e=b.data("timepicker-settings"),f=!1;if(null!==e.minTime&&d<e.minTime?f=!0:null!==e.maxTime&&d>e.maxTime&&(f=!0),a.each(e.disableTimeRanges,function(){return d>=this[0]&&d<this[1]?(f=!0,!1):void 0}),e.forceRoundTime){var g=d%(60*e.step);g>=30*e.step?d+=60*e.step-g:d-=g}var h=r(d,e.timeFormat);f?l(b,h,"error")&&b.trigger("timeRangeError"):l(b,h)}}}function k(a){return a.is("input")?a.val():a.data("ui-timepicker-value")}function l(a,b,c){return a.is("input")&&a.val(b),a.data("ui-timepicker-value")!=b?(a.data("ui-timepicker-value",b),"select"==c?a.trigger("selectTime").trigger("changeTime").trigger("change"):"error"!=c&&a.trigger("changeTime"),!0):(a.trigger("selectTime"),!1)}function m(b){var c=a(this),d=c.data("timepicker-list");if(!d||!d.is(":visible")){if(40!=b.keyCode)return n(b,c);g(c)||c.focus()}switch(b.keyCode){case 13:return p(c)&&y.hide.apply(this),b.preventDefault(),!1;case 38:var e=d.find(".ui-timepicker-selected");return e.length?e.is(":first-child")||(e.removeClass("ui-timepicker-selected"),e.prev().addClass("ui-timepicker-selected"),e.prev().position().top<e.outerHeight()&&d.scrollTop(d.scrollTop()-e.outerHeight())):(d.find("li").each(function(b,c){return a(c).position().top>0?(e=a(c),!1):void 0}),e.addClass("ui-timepicker-selected")),!1;case 40:return e=d.find(".ui-timepicker-selected"),0===e.length?(d.find("li").each(function(b,c){return a(c).position().top>0?(e=a(c),!1):void 0}),e.addClass("ui-timepicker-selected")):e.is(":last-child")||(e.removeClass("ui-timepicker-selected"),e.next().addClass("ui-timepicker-selected"),e.next().position().top+2*e.outerHeight()>d.outerHeight()&&d.scrollTop(d.scrollTop()+e.outerHeight())),!1;case 27:d.find("li").removeClass("ui-timepicker-selected"),y.hide();break;case 9:y.hide();break;default:return n(b,c)}}function n(a,b){return!b.data("timepicker-settings").disableTextInput||a.ctrlKey||a.altKey||a.metaKey||2!=a.keyCode&&a.keyCode<46}function o(b){var c=a(this),d=c.data("timepicker-list");if(!d||!d.is(":visible"))return!0;switch(b.keyCode){case 96:case 97:case 98:case 99:case 100:case 101:case 102:case 103:case 104:case 105:case 48:case 49:case 50:case 51:case 52:case 53:case 54:case 55:case 56:case 57:case 65:case 77:case 80:case 186:case 8:case 46:i(c,d);break;default:return}}function p(a){var b=a.data("timepicker-settings"),c=a.data("timepicker-list"),d=null,e=c.find(".ui-timepicker-selected");if(e.hasClass("ui-timepicker-disabled"))return!1;if(e.length?d=e.data("time"):k(a)&&(d=s(k(a)),i(a,c)),null!==d){var f=r(d,b.timeFormat);l(a,f,"select")}return!0}function q(a){var b,c=Math.round(a/60);if(Math.abs(c)<60)b=[c,x.mins];else if(60==c)b=["1",x.hr];else{var d=(c/60).toFixed(1);"."!=x.decimal&&(d=d.replace(".",x.decimal)),b=[d,x.hrs]}return b.join(" ")}function r(a,b){if(null!==a){for(var c,d,e=new Date(u.valueOf()+1e3*a),f="",g=0;g<b.length;g++)switch(d=b.charAt(g)){case"a":f+=e.getHours()>11?"pm":"am";break;case"A":f+=e.getHours()>11?"PM":"AM";break;case"g":c=e.getHours()%12,f+=0===c?"12":c;break;case"G":f+=e.getHours();break;case"h":c=e.getHours()%12,0!==c&&10>c&&(c="0"+c),f+=0===c?"12":c;break;case"H":c=e.getHours(),f+=c>9?c:"0"+c;break;case"i":var h=e.getMinutes();f+=h>9?h:"0"+h;break;case"s":a=e.getSeconds(),f+=a>9?a:"0"+a;break;default:f+=d}return f}}function s(a){if(""===a)return null;if(!a||a+0==a)return a;"object"==typeof a&&(a=a.getHours()+":"+t(a.getMinutes())+":"+t(a.getSeconds())),a=a.toLowerCase(),new Date(0);var b;if(-1===a.indexOf(":")?(b=a.match(/^([0-9]):?([0-5][0-9])?:?([0-5][0-9])?\s*([pa]?)m?$/),b||(b=a.match(/^([0-2][0-9]):?([0-5][0-9])?:?([0-5][0-9])?\s*([pa]?)m?$/))):b=a.match(/^(\d{1,2})(?::([0-5][0-9]))?(?::([0-5][0-9]))?\s*([pa]?)m?$/),!b)return null;var c,d=parseInt(1*b[1],10);c=b[4]?12==d?"p"==b[4]?12:0:d+("p"==b[4]?12:0):d;var e=1*b[2]||0,f=1*b[3]||0;return 3600*c+60*e+f}function t(a){return("0"+a).slice(-2)}var u=d(),v=86400,w={className:null,minTime:null,maxTime:null,durationTime:null,step:30,showDuration:!1,timeFormat:"g:ia",scrollDefaultNow:!1,scrollDefaultTime:!1,selectOnBlur:!1,disableTouchKeyboard:!0,forceRoundTime:!1,appendTo:"body",disableTimeRanges:[],closeOnWindowScroll:!1,disableTextInput:!1},x={decimal:".",mins:"mins",hr:"hr",hrs:"hrs"},y={init:function(c){return this.each(function(){var d=a(this);if("SELECT"==d[0].tagName){for(var e={type:"text",value:d.val()},f=d[0].attributes,g=0;g<f.length;g++)e[f[g].nodeName]=f[g].nodeValue;var h=a("<input />",e);d.replaceWith(h),d=h}var i=a.extend({},w);c&&(i=a.extend(i,c)),i.lang&&(x=a.extend(x,i.lang)),i=b(i),d.data("timepicker-settings",i),d.prop("autocomplete","off"),d.on("click.timepicker focus.timepicker",y.show),d.on("change.timepicker",j),d.on("keydown.timepicker",m),d.on("keyup.timepicker",o),d.addClass("ui-timepicker-input"),j.call(d.get(0))})},show:function(){var b=a(this),d=b.data("timepicker-settings");g(b)&&b.blur();var f=b.data("timepicker-list");if(!b.prop("readonly")&&(f&&0!==f.length&&"function"!=typeof d.durationTime||(c(b),f=b.data("timepicker-list")),!f.is(":visible"))){y.hide(),f.show(),b.offset().top+b.outerHeight(!0)+f.outerHeight()>a(window).height()+a(window).scrollTop()?f.offset({left:b.offset().left+parseInt(f.css("marginLeft").replace("px",""),10),top:b.offset().top-f.outerHeight()+parseInt(f.css("marginTop").replace("px",""),10)}):f.offset({left:b.offset().left+parseInt(f.css("marginLeft").replace("px",""),10),top:b.offset().top+b.outerHeight()+parseInt(f.css("marginTop").replace("px",""),10)});var i=f.find(".ui-timepicker-selected");if(i.length||(k(b)?i=h(b,f,s(k(b))):d.scrollDefaultNow?i=h(b,f,s(new Date)):d.scrollDefaultTime!==!1&&(i=h(b,f,s(d.scrollDefaultTime)))),i&&i.length){var j=f.scrollTop()+i.position().top-i.outerHeight();f.scrollTop(j)}else f.scrollTop(0);e(d),b.trigger("showTimepicker")}},hide:function(){a(".ui-timepicker-wrapper:visible").each(function(){var b=a(this),c=b.data("timepicker-input"),d=c.data("timepicker-settings");d&&d.selectOnBlur&&p(c),b.hide(),c.trigger("hideTimepicker")})},option:function(c,d){var e=this,f=e.data("timepicker-settings"),g=e.data("timepicker-list");if("object"==typeof c)f=a.extend(f,c);else if("string"==typeof c&&"undefined"!=typeof d)f[c]=d;else if("string"==typeof c)return f[c];return f=b(f),e.data("timepicker-settings",f),g&&(g.remove(),e.data("timepicker-list",!1)),e},getSecondsFromMidnight:function(){return s(k(this))},getTime:function(a){var b=this;return a||(a=new Date),a.setHours(0,0,0,0),new Date(a.valueOf()+1e3*s(k(b)))},setTime:function(a){var b=this,c=r(s(a),b.data("timepicker-settings").timeFormat);l(b,c),b.data("timepicker-list")&&i(b,b.data("timepicker-list"))},remove:function(){var a=this;a.hasClass("ui-timepicker-input")&&(a.removeAttr("autocomplete","off"),a.removeClass("ui-timepicker-input"),a.removeData("timepicker-settings"),a.off(".timepicker"),a.data("timepicker-list")&&a.data("timepicker-list").remove(),a.removeData("timepicker-list"))}};a.fn.timepicker=function(b){return y[b]?y[b].apply(this,Array.prototype.slice.call(arguments,1)):"object"!=typeof b&&b?(a.error("Method "+b+" does not exist on jQuery.timepicker"),void 0):y.init.apply(this,arguments)}});
