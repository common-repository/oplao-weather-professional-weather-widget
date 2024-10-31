// SEARCH FOR LOCATION ID
/*jQuery(document).ready(function ($) {
    $(".awe-location-search-field-oplao").on("keyup",function () {
        $('.countryList').html("");
        console.log("keyup");
        $.getJSON("https://bd.oplao.com/geoLocation/find.json?nameStarts="+$(this).val()+"&max=5", function(data) {
            $.each(data, function(index, element) {
                var str_city= element.name;
                var str_country = element.countryName;
                str_city = str_city.replace("'","\\'");
                str_country = str_country.replace("'","\\'");
                $('.countryList').append($('<div><a href="javascript:;" onclick="SetData(\''+str_country.toString()+'\',\''+str_city.toString()+'\',\''+element.lat.toString()+'\',\''+element.lng.toString()+'\');">'+element.name+', '+element.countryName+'</a></div>'));
            });
        });
    }),2500;
});

function GetLocate(str) {
    jQuery.ajaxSetup({async: true});
    jQuery('.countryList').html("");
    jQuery.getJSON("https://bd.oplao.com/geoLocation/find.json?nameStarts="+str+"&max=5", function(data) {
        jQuery.each(data, function(index, element) {
            var str_city= element.name;
            var str_country = element.countryName;
            str_city = str_city.replace("'","\\'");
            str_country = str_country.replace("'","\\'");
            jQuery('.countryList').append(jQuery('<div><a href="javascript:;" onclick="SetData(\''+str_country.toString()+'\',\''+str_city.toString()+'\',\''+element.lat.toString()+'\',\''+element.lng.toString()+'\');">'+element.name+', '+element.countryName+'</a></div>'));
        });
    });
}

function SetData(country,city,lat,lng) {
    jQuery(".awe-location-search-field-oplao").val(country + ", " + city);
    jQuery(".awe-coordinates-field-oplao").val(lat + "," + lng);
}*/