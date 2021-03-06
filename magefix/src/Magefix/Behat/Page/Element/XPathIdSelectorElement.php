<?php

namespace Magefix\Behat\Page\Element;

/**
 * Class XPathIdSelectorElement
 * 
 * @package Magefix\Behat\Page\Element
 * @author  Carlo Tasca <ctasca.d3@gmail.com>
 */
class XPathIdSelectorElement extends Generic
{
    /**
     * @var array
     */
    protected $selector = ['xpath' => '//*[@id="{parameter}"]'];

    /**
     * setParameter wrapper
     * 
     * @param $id
     */
    public function setId($id) 
    {
        $this->setParameter($id);
    }
}
