        <div class="footer_wrap ">


                <footer id="footer" class="">
                    <div class="sub_footer">
                        <div class="footer-1 low-footer">
                        <?php if (!dynamic_sidebar('Footer-1')) :?> <?php endif;?>
                        </div>
                        <div class="footer-2 low-footer">
                            <?php if (!dynamic_sidebar('Footer-2')) :?> <?php endif;?>
                        </div>
                        <div class="footer-3 low-footer">
                            <?php if (!dynamic_sidebar('Footer-3')) :?> <?php endif;?>
                        </div>
                        <div class="clearfix"></div>
                    </div><!-- /sub_footer -->
                </footer><!-- /footer -->


            <div class="clearfix"></div>
                
        </div><!-- /footer_wrap -->

        </div><!-- /wrap -->

    
        
    <!-- WP_Footer --> 
    <?php wp_footer(); ?>
    <!-- /WP_Footer --> 

      
    </body>
</html>