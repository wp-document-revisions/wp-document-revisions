<?php
/**
 * RSS2 Feed Template for displaying RSS2 Document Revisions feed.
 * Inspired by WP's feed-rss2.php
 *
 * @package WP_Document_Revisions
 */

global $post, $wpdr;
if ( ! $wpdr ) {
	$wpdr = &Document_Revisions::$instance;
}

$rev_query = $wpdr->get_revision_query( $post->ID );

@header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );

echo '<?xml version="1.0" encoding="' . ent2ncr( esc_attr( get_option( 'blog_charset' ) ) ) . '"?' . '>'; ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php do_action( 'rss2_ns' ); ?>>
<channel>
	<title>
	<?php
	bloginfo_rss( 'name' );
	wp_title_rss();
	?>
	</title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss( 'url' ); ?></link>
	<description><?php bloginfo_rss( 'description' ); ?></description>
	<lastBuildDate><?php echo ent2ncr( esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ) ); ?></lastBuildDate>
	<language><?php echo ent2ncr( esc_html( get_option( 'rss_language' ) ) ); ?></language>
	<sy:updatePeriod><?php echo ent2ncr( esc_html( apply_filters( 'rss_update_period', 'hourly' ) ) ); ?></sy:updatePeriod>
	<sy:updateFrequency><?php echo ent2ncr( esc_html( apply_filters( 'rss_update_frequency', '1' ) ) ); ?></sy:updateFrequency>
	<?php
	do_action( 'rss2_head' );
	while ( $rev_query && $rev_query->have_posts() ) :
		$rev_query->the_post();
		?>
			<item>
		<title><?php the_title_rss(); ?></title>
		<link><?php the_permalink_rss(); ?></link>
		<pubDate><?php echo ent2ncr( esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ) ) ); ?></pubDate>
		<dc:creator><?php the_author(); ?></dc:creator>
	<?php the_category_rss( 'rss2' ); ?>
		<guid isPermaLink="false"><?php the_guid(); ?></guid>
		<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
	<?php do_action( 'rss2_item' ); ?>
	</item>
	<?php endwhile; ?>
</channel>
</rss>
