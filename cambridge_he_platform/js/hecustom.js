(function ($) {

    jQuery(document).ready(function () {
            var isbn = '';
            var resultset =[];
            var selected = new Array();    
           
            jQuery('#he_list').DataTable({
                "processing": true,
                'columnDefs': [
                    {
                        'targets': 0,
                        'className': 'dt-body-center',
                        'visible':false,
                        'render': function (data, type, full, meta) {
                            isbn=data;
                            return isbn;
                        }
                    },
                    {
                        'targets': 4,
                        'className': 'dt-body-center',
                        'render': function (data, type, full, meta) {                            
                           if(data==1){
                            return '<input type="checkbox" checked name="id[]" value="' +data+ '" id="'+isbn+'">';
                        }else{
                            return '<input type="checkbox" name="id[]" value="' +data+ '" id="'+isbn+'">';
                       }
                    }
                    }],
                    order: [1, 'asc'],
                    });
                    jQuery("#he_list").on("change", ":checkbox",  function() {
                        var val = jQuery(this).val();
                        var id = jQuery(this).attr('id');                     
                       selected.push(id);                     
                    });
                    $(".button_save").click(function (e) {
                        e.preventDefault();                         
                        //Display the selected CheckBox values.                       
                        if (selected.length > 0) {                           
                            $.ajax({        
                                    url: "/cambridge_he_platform/chkboxdata",        
                                    type: "post", 
                                    datatype:'JSON',       
                                    data: { result: selected },       
                                    success: function(data) {
                                            if(data == 1){
                                              alert(" Data Saved Successfully ");
                                            }else{
                                                alert(" Unable to Update the ISBN Data ");
                                            }  
                                              location.reload(true);
                                            //setInterval('location.reload()', 1000);
                                    },        
                                    error: function(jqXhr, textStatus, errorThrown) {    
                                    console.log(errorThrown);        
                                    }        
                                });

                        }
                    });
            
                    
        });
    
})(jQuery);
