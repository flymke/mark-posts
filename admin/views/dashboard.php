<?php
/**
 * Renders the view for the dashboard widget.
 *
 * @package      Mark_Posts
 * @author       Michael Schoenrock <hello@michaelschoenrock.com>
 * @contributor  Sven Hofmann <info@hofmannsven.com>
 * @license      GPL-2.0+
 * @copyright    2014 Michael Schoenrock
 */

// get marker
$marker_args = array(
  'hide_empty' => true
);
$markers = get_terms( 'marker', $marker_args );

?>

<div class="main">
  <ul id="markers_right_now">
    <?php
      if ( !empty($markers) ) :
        foreach ( $markers as $marker ) :
          echo '<li class="mark-posts-info mark-posts-' . $marker->slug . '"><a href="' . admin_url( 'edit.php?marker=' ) . $marker->slug . '">' . $marker->count . ' ' . $marker->name . '</a></li>';
        endforeach;
      else:
        _e('No marked posts yet.', 'mark-posts');
      endif;
    ?>
  </ul><!-- /#markers_right_now -->
</div><!-- /.main -->