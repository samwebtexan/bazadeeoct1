jQuery(document).ready(function(){
    //get the magnific popup intsnce
    var magnificPopup = jQuery.magnificPopup.instance; 
 
    var jq=jQuery;
    //hide form if already  part of dom
    jq("#bpajax-register-form-container").hide();

    jq(document).on('click', '.bp-signup a, #login-text a, a.bp-ajaxr, #wp-admin-bar-bp-register a, .bp-login-widget-register-link a', function(){
        if(jq("#bpajax-register-form-container").get(0)){
            //we already have the form
            //let us show it
           show_reg_form();
        }else{
            jq.post( ajaxurl, {
                action: 'bpajax_get_register_form',
                'cookie': encodeURIComponent(document.cookie)
                },
                function (response){
                    jq(response).appendTo('body').hide();
                    show_reg_form();
            });
        }//end of else    
        
        return false;
  
    });

    //delegate blog checked
    jq(document).on('click','.mfp-content #signup_with_blog',function(){
        if(jq(this).is(":checked"))
            jq('#blog-details').show()
        else
            jq('#blog-details').hide();
   
    });
    
    //when submit btn is clicked
    jq(document).on('click', '#register_form #signup_submit',function(){
        var $this=jq(this);
           
        var form=jq(jq(this).parents('form').get(0));
            
             
        var data=form.serialize()+'&signup_submit=yes&action=bpajax_register&cookie='+encodeURIComponent(document.cookie)
        jq.post( ajaxurl,data,
                function (response){
                    response=JSON.parse(response);
                    update_content(response.data);
                    //jq($this).trigger('resize.modal');//resize the modal
                    
                    if(response.redirect==1){
                        setTimeout(function(){
                         //$this.parents('.modal-content').append("<h2>You will be redirected in 3 seconds</h2>");   
                         window.location.reload();
                    }, 1000 );//change it to the ms you want to show the users the message
                    }
                    
                }
            );
                
                return false;
        });

    //update the modal box content
    function update_content( content ){
     
        magnificPopup.items= [{
            src: content, // can be a HTML string, jQuery object, or CSS selector
            type: 'inline'
        }]
        

        magnificPopup.updateItemHTML();
        //check for blog form state and update 
        if(jq('.mfp-content #signup_with_blog').is(':checked'))
             jq('#blog-details').show();
    }
    
    //show the form in modal window
    function show_reg_form(){
     
        magnificPopup.open({
            items: {
              src: jq("#bpajax-register-form-container").html(), // can be a HTML string, jQuery object, or CSS selector
              type: 'inline'
            },
            closeBtnInside:true,
            closeOnContentClick: false,
            closeOnBgClick: false
        });
     
    }
    
});
