// AJAX SEARCH BAR AND CATEGORIES DROPDOWN

// Add HTML for the search bar and category dropdown
function custom_ajax_search_html() {
    ?>
   <div class="search-container">
    <div class="search-input-category"> 
        <input type="text" id="ajax-search" placeholder="Search products..." autocomplete="off">
        <select id="ajax-category">
            <option value="">All Categories</option>
            <?php
            $categories = get_terms('product_cat');
            foreach ($categories as $category) {
                echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
            }
            ?>
        </select>
    </div>
</div>

        <div id="ajax-search-results" style="display:none;"></div> <!-- Ensure the results div is hidden initially -->
    </div>
    <?php
}
add_action('woocommerce_before_shop_loop', 'custom_ajax_search_html', 10);


// AJAX handler for product search
add_action('wp_ajax_ajax_search', 'ajax_search');
add_action('wp_ajax_nopriv_ajax_search', 'ajax_search');

function ajax_search() {
    // Get the search term and category from the request
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $category_slug = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

    // Set up the query arguments
    $args = array(
        'post_type' => 'product',
        's' => $search_term,
        'posts_per_page' => 5, // Limit results to 5
    );

    // If a category is selected, add it to the arguments
    if (!empty($category_slug)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $category_slug,
            ),
        );
    }

    // Query the products
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        echo '<div class="ajax-search-results">';
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            ?>
            <div class="search-result">
               
                   <div>
                <a href="<?php echo get_permalink($product->get_id()); ?>">
                    <img src="<?php echo wp_get_attachment_url($product->get_image_id()); ?>" alt="<?php the_title(); ?>" />
                </a>
            </div>
            <div class="product-info">
                <h3>
                    <a href="<?php echo get_permalink($product->get_id()); ?>"><?php the_title(); ?></a>
                </h3>
                <p>
                    <a href="<?php echo get_permalink($product->get_id()); ?>"><?php echo wp_trim_words(get_the_excerpt(), 30); ?></a>
                </p>
            </div>
               
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<p>No products found.</p>';
    }

    // Reset post data
    wp_reset_postdata();
    die(); // Stop further execution
}


// Enqueue AJAX script
add_action('wp_footer', 'ajax_search_script');
function ajax_search_script() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#ajax-search').on('keyup', function() {
            var search_term = $(this).val();
            var category = $('#ajax-category').val();

            if (search_term.length >= 1) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'ajax_search',
                        search_term: search_term,
                        category: category
                    },
                    success: function(data) {
                        $('#ajax-search-results').html(data).show();
                    }
                });
            } else {
                $('#ajax-search-results').hide(); // Hide results when input is empty
            }
        });

        // Hide results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.search-container').length) {
                $('#ajax-search-results').hide();
            }
        });
    });
</script>
    <?php
}
