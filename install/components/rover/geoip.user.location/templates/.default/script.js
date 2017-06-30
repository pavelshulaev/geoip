/**
 * Created by lenovo on 29.06.2017.
 */
$(function(){
    var $updateMarker = $('.update-marker');

    if (!$updateMarker.length){
        return;
    }

    $updateMarker.on('click', function(){
        var $cells = $(this).parent().parent().find('td');

        if (!$cells.length){
            return;
        }

        if ($(this).is(':checked')){
            $cells.addClass('bg-success');
        } else{
            $cells.removeClass('bg-success');
        }
    });
});