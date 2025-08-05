<?php

namespace hardMOB\Afiliados\Connector;

interface StoreInterface
{
    /**
     * Generate affiliate URL for given slug
     * 
     * @param string $slug Product/item identifier
     * @return string Generated affiliate URL
     * @throws \InvalidArgumentException For invalid slugs
     */
    public function generateAffiliateUrl($slug);

    /**
     * Validate if slug is acceptable for this store
     * 
     * @param string $slug Product/item identifier
     * @return bool True if valid, false otherwise
     */
    public function validateSlug($slug);
}
