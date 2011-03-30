var Page = {
load: function(url) {
    console.log('Load method called');
    $.ajax({
        url: url,
        success: function(data) {
            try {
                var jsondata = $.parseJSON(data);
                    for(i in jsondata) {
                        var message = jsondata[i];
                        if(message.redirect) {
                            console.log('I have a redirect page');
                            if(message.redirect.indexOf('http') == 0) {
                                console.log('Redirecting to external site' + message.redirect);
                                window.location = message.redirect;
                            }
                            else {
                                console.log('Redirecting internally: ' + message.redirect);
                                Page.load(message.redirect);
                            }
                        }
                    }
            }
            catch(e) {
                console.log('The page did not contain JSON data');
                Page.removeLoader();
                $('#appspace').html(data);
                console.log('finished loading non-JSON');
                window.history.pushState("string", "title", url); // I don't know what those two are for...
            }
        }
    });
},

addLoader: function() {
    $('#appspace').children().remove();
    var loader = $('<img id="load" src="/img/load.gif"/>');
    $('#appspace').append(loader);
},

removeLoader: function() {
    $('#appspace #load').remove();
}

};

$(document).ready(function() {
    $('a').click(function(event) {
        event.preventDefault();
        Page.addLoader();
        Page.load($(this).attr('href'));
        return false;
    });

    $('form input[type=submit]').click(function(event) {
        event.preventDefault();
        var form = $(this).parents('form');
        var serial_data = form.serialize() + '&' + $(this).attr('name') + '=' + $(this).attr('value');
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            dataType: 'json',
            data: serial_data,
            success: function(data) {

                form.children('.messages').remove();
                form.append('<div class="messages"></div>');

                for(i in data)
                {
                    message = data[i];
                    if(message.text && !message.redirect)
                    {
                        var messages = form.children('.messages');
                        messages.append('<li class="' + message.class + '">' + message.text + '</li>');
                    }
                    if(message.redirect)
                    {
                        form.children('.messages').remove();
                        func = 'Page.load("' + message.redirect + '");';
                        console.log(func);
                        if(message.text)
                        {
                            setTimeout(func, 500);
                        }
                        else
                        {
                            Page.load(message.redirect);
                        }
                    }
                }
            }
        }); 
        return false; 
    }); 
});
