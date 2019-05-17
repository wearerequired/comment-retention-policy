<?php
/**
 * Shims for older WordPress versions.
 *
 * Functionality that is or should be part of newer WordPress versions.
 */

namespace Required\CommentRetentionPolicy\Shims;

/**
 * Inits shims for older WordPress versions.
 */
function bootstrap() {
	add_action( 'parse_comment_query', __NAMESPACE__ . '\add_comment_author_ip_to_default_query_vars' );
	add_filter( 'comments_clauses', __NAMESPACE__ . '\extend_comments_clauses', 10, 2 );
}

/**
 * Adds 'author_ip', 'author_ip__in' and 'author_ip__not_in' to the default query vars.
 *
 * @param \WP_Comment_Query $query The comment query.
 */
function add_comment_author_ip_to_default_query_vars( $query ) {
	$query->query_var_defaults = array_merge(
		$query->query_var_defaults,
		[
			'author_ip'         => '',
			'author_ip__in'     => '',
			'author_ip__not_in' => '',
		]
	);
}

/**
 * Adds WHERE clauses if the 'author_ip', 'author_ip__in' or 'author_ip__not_in' query var is set.
 *
 * @param array             $clauses Site query clauses.
 * @param \WP_Comment_Query $query   The comment query.
 * @return array Comment query clauses.
 */
function extend_comments_clauses( $clauses, $query ) {
	global $wpdb;

	if ( empty( $query->query_vars['author_ip'] ) && empty( $query->query_vars['author_ip__in'] ) && empty( $query->query_vars['author_ip__not_in'] ) ) {
		return $clauses;
	}

	$where = [];

	if ( ! empty( $query->query_vars['author_ip'] ) ) {
		$where[] = $wpdb->prepare( 'comment_author_IP = %s', $query->query_vars['author_ip'] );
	}

	if ( ! empty( $query->query_vars['author_ip__in'] ) ) {
		$sql     = 'comment_author_IP IN (' . implode( ',', array_fill( 0, count( $query->query_vars['author_ip__in'] ), '%s' ) ) . ')';
		$where[] = $wpdb->prepare( $sql, $query->query_vars['author_ip__in'] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	if ( ! empty( $query->query_vars['author_ip__not_in'] ) ) {
		$sql     = 'comment_author_IP NOT IN (' . implode( ',', array_fill( 0, count( $query->query_vars['author_ip__not_in'] ), '%s' ) ) . ')';
		$where[] = $wpdb->prepare( $sql, $query->query_vars['author_ip__not_in'] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	$where = implode( ' AND ', $where );

	if ( empty( $clauses['where'] ) ) {
		$clauses['where'] = $where;
	} else {
		$clauses['where'] .= " AND $where";
	}

	return $clauses;
}
