<?php

namespace App\Service;

use App\Entity\Language;
use Doctrine\Persistence\ManagerRegistry;

class GlobalTemplateService
{
    private $doctrine;
    private $allLanguages;

    public function __construct(ManagerRegistry $doctrine) {
        $this->doctrine = $doctrine;
        $this->allLanguages = $this->doctrine->getRepository(Language::class)->findAll();
    }

    public function getLanguages() : array {
        return $this->allLanguages;
    }


}