<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Collection\Collection;

class Bookmark extends Entity
{
    protected $_accessible = [
        'user_id' => true,
        'title' => true,
        'description' => true,
        'url' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
        'tags' => true,
        'tag_striing' => true,
    ];

    protected function _getTagString()
    {
        if(isset($this->_properties['tag_striing'])){
            return $this->_properties['tag_striing'];
        }
        if(empty($this->tags)){
            return '';
        }
        $tags = new Collection($this->tags);
        $str = $tags->reduce(function ($string, $tag){
            return $string . $tag->title . ', ';
        } , '');
        return trim($str, ' , ');
    }
}
