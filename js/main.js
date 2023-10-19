(function($){

    "use strict";

    /*
        1. Add product to wishlist
        2. Display wishlist items in the table
        3. Remove product from the wishlist

    */

    Array.prototype.unique = function() {
      return this.filter(function (value, index, self) {
        return self.indexOf(value) === index;
      });
    }

    function isInArray(value, array) {return array.indexOf(value) > -1;}

    function onWishlistComplete(target, title){
        setTimeout(function(){
            target
            .removeClass('loading')
            .addClass('active')
            .attr('title',title);
        },800);
    }

    function highlightWishlist(wishlist,title){
        $('.wishlist-toggle').each(function(){
            var $this = $(this);
            var currentProduct = $this.data('product');
            currentProduct = currentProduct.toString();
            if (isInArray(currentProduct,wishlist)) {
                $this.addClass('active').attr('title',title);
            }
        });
    }

    var shopName   = opt.shopName+'-wishlist',
        inWishlist = opt.inWishlist,
        restUrl    = opt.restUrl,
        wishlist   = new Array,
        ls         = sessionStorage.getItem(shopName),
        loggedIn   = ($('body').hasClass('logged-in')) ? true : false,
        userData   = '';

    if(loggedIn) {
        // Fetch current user data
        $.ajax({
            type: 'POST',
            url: opt.ajaxUrl,
            data: {
                'action' : 'fetch_user_data',
                'dataType': 'json'
            },
            success:function(data) {
                userData = JSON.parse(data);
                if (typeof(userData['wishlist']) != 'undefined' && userData['wishlist'] != null && userData['wishlist'] != "") {

                    var userWishlist = userData['wishlist'];
                    userWishlist = userWishlist.split(',');

                    if (wishlist.length) {
                        wishlist =  wishlist.concat(userWishlist);

                        $.ajax({
                            type: 'POST',
                            url:opt.ajaxPost,
                            data:{
                                action:'user_wishlist_update',
                                user_id :userData['user_id'],
                                wishlist :wishlist.join(','),
                            }
                        });

                    } else {
                        wishlist =  userWishlist;
                    }

                    wishlist = wishlist.unique();

                    highlightWishlist(wishlist,inWishlist);
                    sessionStorage.removeItem(shopName);

                } else {
                    if (typeof(ls) != 'undefined' && ls != null) {
                        ls = ls.split(',');
                        ls = ls.unique();
                        wishlist = ls;
                    }

                    $.ajax({
                        type: 'POST',
                        url:opt.ajaxPost,
                        data:{
                            action:'user_wishlist_update',
                            user_id :userData['user_id'],
                            wishlist :wishlist.join(','),
                        }
                    })
                    .done(function(response) {
                        highlightWishlist(wishlist,inWishlist);
                        sessionStorage.removeItem(shopName);
                    });
                }
            },
            error: function(){
                console.log('No user data returned');
            }
        });
    } else {
        if (typeof(ls) != 'undefined' && ls != null) {
            ls = ls.split(',');
            ls = ls.unique();
            wishlist = ls;
        }
    }

    // $('.wishlist-toggle').each(function(){

    $('#add_to_wishlist').on('click',function(e){
        var $this = $(this);

        var currentProduct = $("#product_id").val();
        var list_key = $("#list_key").val();

        currentProduct = currentProduct.toString();

        if (!loggedIn && isInArray(currentProduct,wishlist)) {
            $this.addClass('active').attr('title',inWishlist);
        }

        // $(this).on('click',function(e){
            e.preventDefault();
            // if (!$this.hasClass('active') && !$this.hasClass('loading')) {

                $this.addClass('loading');

                wishlist.push(currentProduct);
                wishlist = wishlist.unique();

                if (loggedIn) {
                    // get user ID
                    if (userData['user_id']) {
                        
                        $.ajax({
                            type: 'POST',
                            url:opt.ajaxPost,
                            data:{
                                action:'user_wishlist_update',
                                user_id :userData['user_id'],
                                list_key : list_key,
                                wishlist :wishlist.join(','),
                            },
                            complete:function(data){
                                // Hide image container
                                $('#exampleModal').modal('hide');
                                location.reload(); 
                            }
                        })
                        .done(function(response) {
                            onWishlistComplete($this, inWishlist);
                        })
                        .fail(function(data) {
                            alert(opt.error);
                        });
                    }
                } else {

                    sessionStorage.setItem(shopName, wishlist.toString());
                    onWishlistComplete($this, inWishlist);

                }

            // }


        // });
    });

    setTimeout(function(){

        if (wishlist.length) {

            restUrl += '?include='+wishlist.join(',');
            restUrl += '&per_page='+wishlist.length;

            $.ajax({
                dataType: 'json',
                url:restUrl
            })
            .done(function(response){
                $('.wishlist-table').each(function(){
                    var $this = $(this);
                    $.each(response,function(index,object){
                        // $this.append('<tr data-product="'+object.id+'"><td><a class="wishlist-remove" href="#" title="'+opt.removeWishlist+'"></a>'+object.image+'</td><td>'+object.title["rendered"]+'</td><td>'+object.price+'</td><td>'+object.stock+'</td><td><a class="details" href="'+object.link+'">'+opt.buttonText+'</a></td></tr>');
                        $this.append('<tr data-product="'+object.id+'"><td width="43%"><a class="wishlist-remove" href="#" title="'+opt.removeWishlist+'"></a>'+object.image+'</td><td width="10.5%">'+object.title["rendered"]+'</td><td width="7.35%">'+object.price+'</td><td width="11.9%">'+object.stock+'</td><td width="14%"><button class="btn btn-primary" id="cart_btn" type="button" onclick="addToCart('+object.id+');">'+opt.cartbuttonText+'</button></td><td width="14%"><a class="details" href="'+object.link+'">'+opt.buttonText+'</a></td></tr>');
                    });
                });
            })
            .fail(function(response){
                alert(opt.noWishlist);
            })
            .always(function(response){
                $('.wishlist-table').each(function(){
                    $(this).removeClass('loading');
                });
            });

        } else {
            $('.wishlist-table').each(function(){
                $(this).removeClass('loading');
            });
        }

    },2000);

    $(document).on('click', '.wishlist-remove', function(){

        var $this = $(this);

        $this.closest('table').addClass('loading');

        wishlist = [];

        $this.closest('table').find('tr').each(function(){

            if ($(this).data('product') != $this.closest('tr').data('product')) {

                wishlist.push($(this).data('product'));

                if (loggedIn) {

                    // get user ID
                    if (userData['user_id']) {
                        $.ajax({
                            type: 'POST',
                            url:opt.ajaxPost,
                            data:{
                                action:'user_wishlist_update',
                                user_id :userData['user_id'],
                                wishlist :wishlist.join(','),
                            }
                        })
                        .done(function(response) {
                            $this.closest('table').removeClass('loading');
                            $this.closest('tr').remove();
                        })
                        .fail(function(data) {
                            alert(opt.error);
                        });
                    }
                } else {
                    sessionStorage.setItem(shopName, wishlist.toString());
                    setTimeout(function(){
                        $this.closest('table').removeClass('loading');
                        $this.closest('tr').remove();
                    },500);
                }
            }

        });

    });

    $("#list_items").change(function(){

        var list_key = $("#list_items").val();
        var $this = $(this);

        
        if(list_key != 0)
        {
            $this.addClass('loading');
            if(loggedIn) {
                // Fetch current user data
                
                $.ajax({
                    type: 'POST',
                    url: opt.ajaxUrl,
                    data: {
                        'action' : 'fetch_user_data',
                        'list_key': list_key,
                        'dataType': 'json'
                    },
                    success:function(data) {
                        
                        userData = JSON.parse(data);
                        if (typeof(userData['wishlist']) != 'undefined' && userData['wishlist'] != null && userData['wishlist'] != "") { 
                            
                            var userWishlist = userData['wishlist'];
                            userWishlist = userWishlist.split(',');
                            var per_page = userWishlist.length; 
                            wishlist =  wishlist.concat(userWishlist);
                            
                            if (wishlist.length) {
                                restUrl += '?include=,'+wishlist.join(',');
                                restUrl += '&per_page='+per_page;
                    
                                $.ajax({
                                    dataType: 'json',
                                    url:restUrl
                                })
                                .done(function(response){
                                    $('.wishlist-table').empty();
                                    $('.wishlist-table').each(function(){
                                        var $this = $(this);
                                        $.each(response,function(index,object){
                                            $this.append('<tr data-product="'+object.id+'"><td width="43%"><a class="wishlist-remove" href="#" title="'+opt.removeWishlist+'"></a>'+object.image+'</td><td width="10.5%">'+object.title["rendered"]+'</td><td width="7.35%">'+object.price+'</td><td width="11.9%">'+object.stock+'</td><td width="14%"><a class="woocommerce-Button button" href="'+object.link+'">'+opt.buttonText+'</a></td><td width="14%"><a class="details" href="'+object.link+'">'+opt.buttonText+'</a></td></tr>');
                                        });
                                    });
                                })
                                .fail(function(response){
                                    alert(opt.noWishlist);
                                })
                    
                            }
        
                        }
                        else{
                            $('.wishlist-table').empty();
                            $(".wishlist-table").append('<tr><td colspan="5" align="center">No Results</td></tr>');
                            }
                    },
                    error: function(){
                        console.log('No user data returned');
                    }
                });
            } else {
                if (typeof(ls) != 'undefined' && ls != null) {
                    ls = ls.split(',');
                    ls = ls.unique();
                    wishlist = ls;
                }
            }            
        }
    }); 

    $("#delete_wishlist").on('click',function(){

        var $this = $(this);      

        var selected_key = $("#list_items").children("option:selected").val();

        // alert(selected_key);
    
        $.ajax({
            type: 'POST',
            url: opt.ajaxUrl,
            data: {
                'action' : 'wishlist_delete',
                'list_key': selected_key,
                'dataType': 'json' 
            },
            success:function(data){
                // Hide image container
                location.reload(); 
            }
        });
    });    
})(jQuery);
function addToCart(p_id) {
    $.get('/wp/?post_type=product&add-to-cart=' + p_id, function() {
    });
    location.reload();
}