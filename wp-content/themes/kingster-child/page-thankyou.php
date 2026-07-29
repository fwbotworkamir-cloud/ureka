<?php
/**
 * Template Name: Thank You (lead conversion)
 *
 * Standalone confirmation page for the ALS and IPP application forms.
 * Its own URL is what makes the lead measurable: GA4 `generate_lead` and the
 * Google Ads conversion both fire here rather than on an inline panel.
 *
 * Which programme it is comes from the page slug (thank-you-als / thank-you-ipp).
 * IPP additionally passes ?r=<readiness> so the sales next-step line survives
 * the redirect from the multi-step form.
 */

$slug = get_post_field( 'post_name', get_the_ID() );
$is_ipp = ( false !== strpos( $slug, 'ipp' ) );

$programme = $is_ipp ? 'International Project Programme' : 'Academic Leadership Suite';
$back_url  = $is_ipp ? '/ipp/' : '/als/';
$ga_form   = $is_ipp ? 'IPP Application' : 'ALS Application';

// Sales next-step message, mirrored from the IPP form's original branching.
$readiness = isset( $_GET['r'] ) ? sanitize_text_field( wp_unslash( $_GET['r'] ) ) : '';
$next_step = '';
if ( 'Ready to proceed' === $readiness ) {
	$next_step = '<b>Next step.</b> Because you indicated you are ready to proceed, we will send your selection-interview invitation and enrolment instructions first.';
} elseif ( in_array( $readiness, array( 'Need more information', 'Comparing programmes' ), true ) ) {
	$next_step = '<b>Next step.</b> We will offer you a short call with the programme team so you can ask questions before deciding.';
} elseif ( in_array( $readiness, array( 'Explore funding', 'Institutional approval' ), true ) ) {
	$next_step = '<b>Next step.</b> We will include a formal programme letter you can use to support a funding or institutional-approval request.';
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Application received — <?php echo esc_html( $programme ); ?> | Ureka Education</title>
<link rel="icon" href="/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">

<!-- Google tag (gtag.js) — GA4 + Ads -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-0BJ1XCV8LZ"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-0BJ1XCV8LZ');
<?php if ( defined( 'UREKA_ADS_ID' ) && UREKA_ADS_ID ) : ?>
gtag('config', '<?php echo esc_js( UREKA_ADS_ID ); ?>');
<?php endif; ?>
gtag('event', 'generate_lead', {
  form: <?php echo wp_json_encode( $ga_form ); ?>,
  programme: <?php echo wp_json_encode( $programme ); ?>,
  readiness: <?php echo wp_json_encode( $readiness ); ?>
});
<?php if ( defined( 'UREKA_ADS_ID' ) && UREKA_ADS_ID && defined( 'UREKA_ADS_LABEL' ) && UREKA_ADS_LABEL ) : ?>
gtag('event', 'conversion', {
  send_to: '<?php echo esc_js( UREKA_ADS_ID . '/' . UREKA_ADS_LABEL ); ?>'
});
<?php endif; ?>
</script>

<style>
  :root{--navy:#1C2A44;--ink:#101F2E;--paper:#F2EFE9;--rule:#EAE6DB;--sage:#4E6B58;--mute:#5a5a5a}
  *{box-sizing:border-box}
  body{margin:0;background:var(--navy);color:#fff;font-family:'Jost',sans-serif;font-weight:300;
       min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
  .card{max-width:620px;width:100%;background:#fff;color:var(--ink);border-radius:10px;
        padding:3rem 2.5rem;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.35)}
  .mark{width:56px;height:56px;border-radius:50%;background:var(--sage);color:#fff;display:flex;
        align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:26px;line-height:1}
  h1{font-family:'Cormorant Garamond',serif;font-weight:400;font-size:clamp(26px,4vw,36px);
     margin:0 0 .5rem;color:var(--navy)}
  .prog{font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--sage);
        font-weight:500;margin-bottom:1.5rem}
  p{font-size:15px;line-height:1.75;color:var(--mute);margin:0 0 1rem}
  .next{background:var(--paper);border:1px solid var(--rule);border-radius:6px;padding:1rem 1.25rem;
        font-size:14px;line-height:1.7;color:var(--ink);text-align:left;margin:1.5rem 0}
  .actions{margin-top:2rem;display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
  .btn{display:inline-block;padding:11px 22px;border-radius:24px;font-size:12px;letter-spacing:.08em;
       text-transform:uppercase;text-decoration:none;font-weight:500}
  .btn-p{background:var(--navy);color:#fff}
  .btn-s{border:1px solid var(--rule);color:var(--ink)}
  .foot{margin-top:2rem;font-size:12px;color:#8a8a8a}
  .foot a{color:var(--sage)}
  @media(max-width:520px){.card{padding:2rem 1.5rem}}
</style>
</head>
<body>
  <main class="card">
    <div class="mark" aria-hidden="true">&#10003;</div>
    <div class="prog"><?php echo esc_html( $programme ); ?></div>
    <h1>Application received</h1>
    <p>Thank you for applying. Our team has your application and will be in touch by email
       — usually within two working days.</p>
    <?php if ( $next_step ) : ?>
      <div class="next"><?php echo wp_kses_post( $next_step ); ?></div>
    <?php endif; ?>
    <p>If you need to reach us sooner, write to
       <a href="mailto:malak.j@ureka.co.uk" style="color:var(--sage)">malak.j@ureka.co.uk</a>.</p>
    <div class="actions">
      <a class="btn btn-p" href="<?php echo esc_url( $back_url ); ?>">Back to the programme</a>
      <a class="btn btn-s" href="https://ureka.co.uk/">Ureka Education</a>
    </div>
    <div class="foot">Delivered by UNITAR &amp; Ureka Education Group &middot; Geneva</div>
  </main>
</body>
</html>
