<?php
class Config {

    // BASIC

    const BASE_URL                 = 'http://localhost/folding-at-home-server/src';
    const LANGUAGE                 = 'english';
    const DEBUG_MODE               = FALSE;

    // DB
    const DB_HOST       = '';
    const DB_NAME       = '';
    const DB_USERNAME   = '';
    const DB_PASSWORD   = '';

    // CAS

    const CAS_DEBUG = TRUE;
    const CAS_DISABLE_SERVER_VALIDATION = TRUE;
    const VENDOR_CAS_SOURCE = '../vendor/jasig/phpcas/source';
    const CAS_HOST = 'cas.sustech.edu.cn';
    const CAS_CONTEXT = '/cas';
    const CAS_PORT = 443;

}
?>
