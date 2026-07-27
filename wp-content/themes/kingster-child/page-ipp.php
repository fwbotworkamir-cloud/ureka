<?php
/**
 * Template Name: IPP Prospectus (standalone)
 *
 * Serves the International Project Programme single-file prospectus verbatim,
 * bypassing the kingster header/footer — it is a self-contained
 * landing page with its own nav, fonts, and styles.
 */
readfile( __DIR__ . '/ipp-app.html' );
