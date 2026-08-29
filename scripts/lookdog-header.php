<?php
/**
 * LookDog - one navigation, at every width.
 *
 * The desktop header laid twelve menu items in a row across the top of the
 * homepage, and the homepage header is transparent - it sits *on* the hero
 * photograph. The links were `#2a2a2a`, near black, over a navy-scrimmed
 * photograph. Unreadable, and it got worse the further right you read, because
 * the scrim is a gradient that lightens towards the right of the image.
 *
 * Twelve items was also too many to scan. So the header is now the same thing
 * at every width: logo left, a three-line toggle top right, and one panel with
 * every category in it.
 *
 * HOW THE BREAKPOINT IS SET, because the obvious way does not work. Astra has a
 * `mobile-header-breakpoint` setting, and with the Header Footer Builder active
 * it is ignored entirely - `astra_header_break_point()` reads
 * `astra_get_tablet_breakpoint()` instead. Raising *that* would move the tablet
 * breakpoint for every responsive rule on the site, which is far too broad. The
 * `astra_header_break_point` filter changes the header alone, which is the
 * whole intent. The setting is deliberately left empty so nobody reads a value
 * there and believes it.
 *
 * Everything else is Astra's own: its toggle button, its ARIA, its open/close
 * JavaScript. Only the appearance is ours.
 *
 * Deployed to: wp-content/novamira-sandbox/lookdog-header.php
 */

defined( 'ABSPATH' ) || exit;

/** 3000px covers 4K. Above that Astra would fall back to the inline row. */
add_filter( 'astra_header_break_point', static fn() => 3000 );

add_action(
	'wp_head',
	static function () {
		?>
<style id="lookdog-header-css">
/* ------------------------------------------------------- which header shows
   The `astra_header_break_point` filter moves the body class and the toggle
   JavaScript, but NOT these two rules: Astra generates them from
   astra_get_tablet_breakpoint() and hardcodes 921/922 into the inline CSS. Left
   alone, the twelve-item row still renders above 922px and the burger header
   stays hidden - the filter alone changes nothing you can see. !important is
   the right tool here: these override generated theme output whose source order
   is not ours to rely on. */
@media (min-width:922px){
	#ast-desktop-header{display:none!important}
	#ast-mobile-header{display:block!important}
	/* Line the logo and the toggle up with the 1200px content column rather
	   than letting them hug the window edges on a wide screen. */
	#ast-mobile-header .ast-primary-header-bar > .ast-builder-grid-row{
		max-width:1200px;margin-left:auto;margin-right:auto;padding-left:40px;padding-right:40px;
	}
}

/* ---------------------------------------------------------------- toggle */
.ast-header-break-point .main-header-menu-toggle,
.menu-toggle.main-header-menu-toggle{
	display:inline-flex;align-items:center;justify-content:center;
	width:44px;height:44px;padding:0;border-radius:4px;
	background:transparent;border:1px solid transparent;
	color:#14213D;
	transition:background .18s ease,border-color .18s ease,color .18s ease;
}
.menu-toggle.main-header-menu-toggle:hover,
.menu-toggle.main-header-menu-toggle:focus{background:rgba(20,33,61,.07);color:#14213D}
.menu-toggle.main-header-menu-toggle:focus-visible{outline:3px solid #F97316;outline-offset:2px}
.menu-toggle .ast-mobile-svg{width:26px;height:26px}

/* On the homepage the header sits on the photograph, so the icon is white and
   carries a hairline to hold its edge against a busy image.

   !important, because Astra styles `.menu-toggle` with its global *button*
   rule - the same one that paints the orange pill buttons - and then layers
   an `ast-mobile-menu-trigger-outline` treatment on top. The bars are an SVG
   using fill="currentColor", so whatever wins the `color` cascade is the
   colour of the three lines. */
.ast-theme-transparent-header .menu-toggle.main-header-menu-toggle,
.ast-theme-transparent-header .menu-toggle.main-header-menu-toggle .ast-mobile-svg,
.ast-theme-transparent-header .menu-toggle.main-header-menu-toggle .ast-close-svg{
	color:#FFFFFF!important;fill:#FFFFFF!important;
}
.ast-theme-transparent-header .menu-toggle.main-header-menu-toggle{
	border-color:rgba(255,255,255,.6)!important;background:rgba(20,33,61,.28)!important;
}
.ast-theme-transparent-header .menu-toggle.main-header-menu-toggle:hover,
.ast-theme-transparent-header .menu-toggle.main-header-menu-toggle:focus{
	background:rgba(255,255,255,.2)!important;border-color:#FFFFFF!important;
}

/* Move the bar clear of the top edge on the photographic header. The logo and
   the toggle travel together, so they stay on one line. */
.ast-theme-transparent-header #ast-mobile-header .ast-primary-header-bar,
.ast-theme-transparent-header #ast-desktop-header .ast-primary-header-bar{
	padding-top:2.5cm;
}

/* ----------------------------------------------------------------- panel */
.ast-header-break-point .ast-mobile-header-content{
	background:#FFFFFF;
	border-top:1px solid #E6E6E1;
	box-shadow:0 14px 34px rgba(20,33,61,.14);
	max-height:calc(100vh - 96px);overflow-y:auto;
}
.ast-header-break-point .ast-mobile-header-content .main-header-menu{
	max-width:1200px;margin:0 auto;padding:6px 0;width:100%;
}
.ast-header-break-point .ast-mobile-header-content .menu-item{
	border-bottom:1px solid #EFEFEC;
}
.ast-header-break-point .ast-mobile-header-content .menu-item:last-child{border-bottom:0}
/* THE PANEL WAS RENDERING WHITE-ON-WHITE ON THE HOMEPAGE.

   Astra's transparent-header rule paints every `.main-header-menu .menu-link`
   white so the old inline row could sit on the hero photograph. That selector
   does not know the difference between a link in the header bar and a link
   inside the dropdown - and the dropdown is the same menu. So on a white panel
   the items were white text: present, clickable, invisible. It beat our colour
   on specificity (five classes to three), which is why !important is here and
   not a nicer selector. */
.ast-header-break-point .ast-mobile-header-content .menu-link{
	display:block;padding:15px 26px;
	color:#14213D!important;font-size:16px;font-weight:600;line-height:1.3;
	text-decoration:none;transition:background .16s ease,color .16s ease;
}
.ast-header-break-point .ast-mobile-header-content .menu-link:hover,
.ast-header-break-point .ast-mobile-header-content .menu-link:focus{
	background:#F8F8F6;color:#EA670B!important;
}
.ast-header-break-point .ast-mobile-header-content .menu-link:focus-visible{
	outline:3px solid #F97316;outline-offset:-3px;
}
.ast-header-break-point .ast-mobile-header-content .current-menu-item > .menu-link{
	color:#EA670B!important;box-shadow:inset 3px 0 0 #F97316;
}

/* Blog is where the shop ends and the rest of the site starts. One rule says
   so without needing a heading. */
.ast-header-break-point .ast-mobile-header-content .menu-item-3281{
	border-top:1px solid #D5D5CE;margin-top:6px;
}

/* The logo keeps its own size at every width now that the row never renders. */
.ast-header-break-point #ast-mobile-header .custom-logo{max-width:120px;height:auto}

@media (max-width:640px){
	.ast-header-break-point .ast-mobile-header-content .menu-link{padding:14px 20px;font-size:15.5px}
	.ast-header-break-point .ast-mobile-header-content{max-height:70vh}
	/* 2.5cm is a quarter of a phone screen. Half of it reads the same there. */
	.ast-theme-transparent-header #ast-mobile-header .ast-primary-header-bar,
	.ast-theme-transparent-header #ast-desktop-header .ast-primary-header-bar{padding-top:1.25cm}
}
@media (prefers-reduced-motion: reduce){
	.menu-toggle.main-header-menu-toggle,
	.ast-header-break-point .ast-mobile-header-content .menu-link{transition:none}
}
</style>
		<?php
	},
	21
);
