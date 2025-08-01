<?php

namespace hardMOB\Afiliados\Connector;

interface StoreInterface
{
    public function generateAffiliateUrl($slug);
    public function validateSlug($slug);
}
