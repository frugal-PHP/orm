<?php

namespace FrugalPhpPlugin\Orm\Entities;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait SoftDeleteTrait
{
    #[ORM\Column(type: "datetime")]
    public DateTime $deletedAt;

    
}