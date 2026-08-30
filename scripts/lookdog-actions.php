<?php
/**
 * LookDog - the owner's own list, on the dashboard.
 *
 * Everything else on the dashboard is measured: clicks, prices, cron runs,
 * products the supplier withdrew. This is the other half - the things a machine
 * cannot see are outstanding, because they live in somebody's head or in a
 * conversation. Without somewhere to put them they get remembered at the wrong
 * moment and done never.
 *
 * Kept deliberately small. No due dates, no priorities, no projects: a list you
 * have to maintain is one more thing to maintain. An item is text, the date it
 * was added, and whether it is done.
 *
 * Stored in the `lookdog_actions` option rather than as posts, because these are
 * notes rather than content and have no business in the database's content
 * tables, the sitemap, or a search result.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-actions.php
 */

defined( 'ABSPATH' ) || exit;

/** How many finished items stay visible before they are dropped. */
const LOOKDOG_ACTIONS_KEEP_DONE = 4;

/**
 * The whole list, oldest first.
 *
 * @return array<int,array<string,mixed>>
 */
function lookdog_actions() {
	$items = get_option( 'lookdog_actions', array() );
	return is_array( $items ) ? $items : array();
}

/** Just the outstanding ones. */
function lookdog_actions_open() {
	return array_values(
		array_filter(
			lookdog_actions(),
			static function ( $i ) {
				return empty( $i['done'] );
			}
		)
	);
}

/**
 * Add an item, skipping anything already on the list word for word.
 *
 * @param string $text What to do.
 * @param string $note Why, or how - one line, optional.
 * @return bool Whether it was added.
 */
function lookdog_actions_add( $text, $note = '' ) {
	$text = trim( wp_strip_all_tags( (string) $text ) );
	if ( '' === $text ) {
		return false;
	}

	$items = lookdog_actions();
	foreach ( $items as $i ) {
		if ( strcasecmp( $i['text'], $text ) === 0 && empty( $i['done'] ) ) {
			return false;
		}
	}

	$items[] = array(
		'id'    => wp_generate_password( 8, false, false ),
		'text'  => $text,
		'note'  => trim( wp_strip_all_tags( (string) $note ) ),
		'added' => current_time( 'Y-m-d' ),
		'done'  => false,
	);
	update_option( 'lookdog_actions', $items, false );
	return true;
}

/**
 * Tick, untick or remove one item.
 *
 * @param string $id  Item id.
 * @param string $act done|undo|del.
 * @return void
 */
function lookdog_actions_do( $id, $act ) {
	$items = lookdog_actions();
	$out   = array();
	$done  = 0;

	foreach ( $items as $i ) {
		if ( $i['id'] === $id ) {
			if ( 'del' === $act ) {
				continue;
			}
			$i['done']    = ( 'done' === $act );
			$i['done_at'] = $i['done'] ? current_time( 'Y-m-d' ) : '';
		}
		$out[] = $i;
	}

	// Finished items are kept for a few days so a tick can be undone and so the
	// list shows some evidence of progress, then quietly dropped. A to-do list
	// that only grows is a list people stop opening.
	$kept = array();
	foreach ( array_reverse( $out ) as $i ) {
		if ( ! empty( $i['done'] ) ) {
			if ( $done >= LOOKDOG_ACTIONS_KEEP_DONE ) {
				continue;
			}
			$done++;
		}
		$kept[] = $i;
	}

	update_option( 'lookdog_actions', array_reverse( $kept ), false );
}

/** One link that ticks, unticks or deletes, carrying its own nonce. */
function lookdog_actions_link( $id, $act ) {
	return wp_nonce_url(
		add_query_arg(
			array(
				'ld_act' => $act,
				'ld_id'  => $id,
			),
			home_url( '/dashboard/' )
		),
		'lookdog_action_' . $id
	);
}

add_action(
	'template_redirect',
	static function () {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$dash = home_url( '/dashboard/' );

		if ( isset( $_POST['ld_act_add'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			check_admin_referer( 'lookdog_action_add' );
			lookdog_actions_add( wp_unslash( $_POST['ld_act_add'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			wp_safe_redirect( $dash, 303 );
			exit;
		}

		if ( ! isset( $_GET['ld_act'], $_GET['ld_id'] ) ) {
			return;
		}

		$id  = sanitize_text_field( wp_unslash( $_GET['ld_id'] ) );
		$act = sanitize_text_field( wp_unslash( $_GET['ld_act'] ) );
		if ( ! in_array( $act, array( 'done', 'undo', 'del' ), true ) ) {
			return;
		}
		check_admin_referer( 'lookdog_action_' . $id );

		lookdog_actions_do( $id, $act );
		wp_safe_redirect( $dash, 303 );
		exit;
	},
	// Ahead of lookdog_dash_render(), which also hooks template_redirect and
	// renders the page. At an equal priority this would depend on which file
	// the sandbox happened to load first, and a tick would silently do nothing.
	0
);

/**
 * The dashboard card. Uses the dashboard's own classes so it inherits the rest
 * of the design; only the few things that do not exist there are styled here.
 */
function lookdog_actions_card() {
	$items = lookdog_actions();
	$open  = array();
	$done  = array();
	foreach ( $items as $i ) {
		if ( empty( $i['done'] ) ) {
			$open[] = $i;
		} else {
			$done[] = $i;
		}
	}
	?>
<style>
.todo{display:flex;align-items:flex-start;gap:12px;padding:12px 0;
	border-bottom:1px solid var(--line);min-height:44px}
.todo:last-of-type{border-bottom:0}
.todo__tick{flex:0 0 auto;width:26px;height:26px;margin-top:1px;border:2px solid var(--line);
	border-radius:8px;display:flex;align-items:center;justify-content:center;
	color:var(--muted);text-decoration:none;font-size:15px;line-height:1}
.todo__tick:active{border-color:var(--accent);color:var(--accent)}
.todo__body{flex:1 1 auto;min-width:0}
.todo__text{display:block;color:var(--ink);font-size:14.5px;line-height:1.4}
.todo__meta{display:block;margin-top:3px;color:var(--muted);font-size:12.5px;line-height:1.4}
.todo--done .todo__text{color:var(--muted);text-decoration:line-through}
.todo--done .todo__tick{border-color:var(--good);color:var(--good)}
.todo__add{display:flex;gap:8px;margin-top:14px}
.todo__add input{flex:1 1 auto;min-width:0;background:var(--bg);color:var(--ink);
	border:1px solid var(--line);border-radius:10px;padding:11px 12px;font-size:15px;
	min-height:44px;font-family:inherit}
.todo__add button{flex:0 0 auto;background:var(--ink);color:#fff;border:0;border-radius:10px;
	padding:0 16px;min-height:44px;font-size:14px;font-weight:600;font-family:inherit}
</style>
<div class="card">
	<h2>Your list<?php if ( $open ) : ?><span class="pill"><?php echo esc_html( (string) count( $open ) ); ?></span><?php endif; ?></h2>

	<?php if ( ! $open && ! $done ) : ?>
		<p class="muted">Nothing on it. Add something below and it will still be here next time.</p>
	<?php endif; ?>

	<?php foreach ( $open as $i ) : ?>
		<div class="todo">
			<a class="todo__tick" href="<?php echo esc_url( lookdog_actions_link( $i['id'], 'done' ) ); ?>"
			   aria-label="<?php esc_attr_e( 'Mark done', 'lookdog' ); ?>">&nbsp;</a>
			<span class="todo__body">
				<span class="todo__text"><?php echo esc_html( $i['text'] ); ?></span>
				<span class="todo__meta">
					<?php
					if ( '' !== $i['note'] ) {
						echo esc_html( $i['note'] ) . ' &middot; ';
					}
					printf(
						/* translators: %s: date the item was added. */
						esc_html__( 'added %s', 'lookdog' ),
						esc_html( date_i18n( 'j M', strtotime( $i['added'] ) ) )
					);
					?>
				</span>
			</span>
		</div>
	<?php endforeach; ?>

	<?php foreach ( $done as $i ) : ?>
		<div class="todo todo--done">
			<a class="todo__tick" href="<?php echo esc_url( lookdog_actions_link( $i['id'], 'undo' ) ); ?>"
			   aria-label="<?php esc_attr_e( 'Put back on the list', 'lookdog' ); ?>">&check;</a>
			<span class="todo__body">
				<span class="todo__text"><?php echo esc_html( $i['text'] ); ?></span>
			</span>
		</div>
	<?php endforeach; ?>

	<form class="todo__add" method="post" action="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>">
		<?php wp_nonce_field( 'lookdog_action_add' ); ?>
		<input type="text" name="ld_act_add" placeholder="<?php esc_attr_e( 'Add something', 'lookdog' ); ?>"
		       maxlength="160" autocomplete="off" />
		<button type="submit"><?php esc_html_e( 'Add', 'lookdog' ); ?></button>
	</form>
</div>
	<?php
}
