<?php
namespace Illumina\CasBundle\DependencyInjection;

use Illumina\CasBundle\DependencyInjection\ContentRetrieverService;
use Illumina\PhphealthvaultBundle\DependencyInjection\BaseVocabulary;

class CasVocabulary extends BaseVocabulary
{
    protected $cas;

    public function __construct(ContentRetrieverService $contentRetrieverService)
    {
        $this->cas = $contentRetrieverService;
    }

    public function get($name, $family = 'wc'){

        $key = sprintf('%s-%s', $name, $family);

        if ( ! isset(self::$vocabularies[$key])) {

            $data = $this->cas->retrieveList($name);

            $vocabulary = array();

            foreach($data->results as $term ){

                $vocabulary[$term->taxonomy_term_data_name] = $term->taxonomy_term_data_description;

            }

            self::$vocabularies[$key] = $vocabulary;

        }

        return self::$vocabularies[$key];

    }

    public function supports($name,$family){

        if($family == 'dls'){

            return TRUE;

        }

        return FALSE;

    }
}
