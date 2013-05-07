DLTA_widget = function() {
    this.init();
}

jQuery.extend(DLTA_widget.prototype, {

    // object variables
    widget: '.widget.DL_TA_widget_DL_TA_basic .twitter-timeline',

    init: function() {
    // do initialization here
        this.loadTweets();
    },

    loadTweets: function() {
        // an example object method
        jQuery(this.widget);
        
        var items = [];
        var i = 0;
        
		jQuery.get(
            dlTA.ajaxurl,
            {
                action	: dlTA.action,
                nonce  	: dlTA.nonce
            },
            function( response, textStatus, jqXHR ){
                if( 200 == jqXHR.status && 'success' == textStatus ) {
                    if( 'success' == response.status ){
                        
                        json = jQuery.parseJSON(response.data);
                        
                        jQuery.each(json, function(i, val){
                        
                            key = val.id_str;
                            user_guid = 'http://twitter.com/' + val.user.screen_name;
                            guid = user_guid + '/status/' + val.id_str
                            styleme = '<div class="user"><a href="'+user_guid+'"><img src="'+val.user.profile_image_url+'" alt="'+val.user.name+'"/><h4>'+val.user.name+' <em>@'+val.user.screen_name+'</em><span class="date">'+formatDate(new Date(val.js_timestamp*1000), '%H:%m on %d %M %Y')+'</span></h4></a></div><div class="tweet"><p class="text">'+val.text+'<a href="'+guid+'" alt="details" class="guid">details</a></p></div>';

                            items.push('<li id="' + key + '">' + styleme + '</li>');
                        });
                        
                    } else {
                        console.log("Error with tweet stream widget.");
                    }
                    
                    jQuery('<ul/>', {
                        'class': 'aggregated-tweet-feed', html: items.join('')
                    }).linkify().appendTo('.twitter-timeline');
                }
            },
            'json'
		);



    }
});


jQuery(function(){

    new DLTA_widget(); 

});




function formatDate(date, fmt) {
    function pad(value) {
        return (value.toString().length < 2) ? '0' + value : value;
    }
    return fmt.replace(/%([a-zA-Z])/g, function (_, fmtCode) {
        switch (fmtCode) {
        case 'Y':
            return date.getUTCFullYear();
        case 'M':
            return get_month_name(date);
        case 'd':
            return date.getUTCDate() + get_nth_suffix(date.getUTCDate());
        case 'H':
            return pad(date.getUTCHours());
        case 'm':
            return pad(date.getUTCMinutes());
        case 's':
            return pad(date.getUTCSeconds());
        default:
            throw new Error('Unsupported format code: ' + fmtCode);
        }
    });
}

function get_nth_suffix(date) {
  switch (date) {
    case 1:
    case 21:
    case 31:
       return 'st';
    case 2:
    case 22:
       return 'nd';
    case 3:
    case 23:
       return 'rd';
    default:
       return 'th';
  }
}


function get_month_name(date){
    var monthNames = [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ];
    return monthNames[date.getMonth()];
}

// encoding: utf-8
// $.fn.linkify 1.0 - MIT/GPL Licensed - More info: http://github.com/maranomynet/linkify/
(function(b){var x=/(^|["'(\s]|&lt;)(www\..+?\..+?)((?:[:?]|\.+)?(?:\s|$)|&gt;|[)"',])/g,y=/(^|["'(\s]|&lt;)((?:(?:https?|ftp):\/\/|mailto:).+?)((?:[:?]|\.+)?(?:\s|$)|&gt;|[)"',])/g,z=function(h){return h.replace(x,'$1<a href="<``>://$2">$2</a>$3').replace(y,'$1<a href="$2">$2</a>$3').replace(/"<``>/g,'"http')},s=b.fn.linkify=function(c){if(!b.isPlainObject(c)){c={use:(typeof c=='string')?c:undefined,handleLinks:b.isFunction(c)?c:arguments[1]}}var d=c.use,k=s.plugins||{},l=[z],f,m=[],n=c.handleLinks;if(d==undefined||d=='*'){for(var i in k){l.push(k[i])}}else{d=b.isArray(d)?d:b.trim(d).split(/ *, */);var o,i;for(var p=0,A=d.length;p<A;p++){i=d[p];o=k[i];if(o){l.push(o)}}}this.each(function(){var h=this.childNodes,t=h.length;while(t--){var e=h[t];if(e.nodeType==3){var a=e.nodeValue;if(a.length>1&&/\S/.test(a)){var q,r;f=f||b('<div/>')[0];f.innerHTML='';f.appendChild(e.cloneNode(false));var u=f.childNodes;for(var v=0,g;(g=l[v]);v++){var w=u.length,j;while(w--){j=u[w];if(j.nodeType==3){a=j.nodeValue;if(a.length>1&&/\S/.test(a)){r=a;a=a.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');a=b.isFunction(g)?g(a):a.replace(g.re,g.tmpl);q=q||r!=a;r!=a&&b(j).after(a).remove()}}}}a=f.innerHTML;if(n){a=b('<div/>').html(a);m=m.concat(a.find('a').toArray().reverse());a=a.contents()}q&&b(e).after(a).remove()}}else if(e.nodeType==1&&!/^(a|button|textarea)$/i.test(e.tagName)){arguments.callee.call(e)}}});n&&n(b(m.reverse()));return this};s.plugins={mailto:{re:/(^|["'(\s]|&lt;)([^"'(\s&]+?@.+\.[a-z]{2,7})(([:?]|\.+)?(\s|$)|&gt;|[)"',])/gi,tmpl:'$1<a href="mailto:$2">$2</a>$3'}}})(jQuery);