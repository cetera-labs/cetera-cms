<?php
/**
 * Fastsite CMS 3 
 *
 * @package FastsiteCMS
 * @version $Id$
 * @copyright 2000-2010 Cetera labs (http://www.cetera.ru) 
 * @author Roman Romanov <nicodim@mail.ru> 
 **/
 
namespace Cetera\Iterator;
 
/**
 * Итератор объектов, на которые ссылается поле 
 *
 * @package FastsiteCMS
 **/
class Linkset extends DynamicObject {
	
	
    /**
     * Конструктор              
     *  
     * @param Object $object               
     * @return void  
     */ 
    public function __construct($object, $field)
    {

		if ($field['type'] != FIELD_LINKSET && $field['type'] != FIELD_MATSET) 
			throw new \Cetera\Exception\CMS('Illegal type of field '.$field['name'].' - '.$field['type']);

		/*
        if ($field['type'] == FIELD_LINKSET) {
        		
			if ($field['len'] == CATALOG_VIRTUAL_USERS) {
			    $od = \Cetera\User::getObjectDefinition();
			} elseif ($field['pseudo_type'] == PSEUDO_FIELD_CATOLOGS) {
			    $od = \Cetera\Catalog::getObjectDefinition();
			} elseif (!$field['len']) {
				$od = $object->objectDefinition;		
			} else {
			    $c = \Cetera\Catalog::getById($field['len']);
			    if (!$c) throw new \CeteraException\CMS('Catalog '.$field['len'].' is not found.');
			    $od = $c->materialsObjectDefinition;
			} 
          
        } 
		else {            
            $od = \Cetera\ObjectDefinition::findById($field['len']);
        }
*/		
		
		parent::__construct( $field->getObjectDefinition() );  

		//$linktable = $object->table.'_'.$od->table.'_'.$field['name'];
		
		$this->query->innerJoin('main', $field->getLinkTable(), 'b', 'main.id = b.dest and b.id='.(int)$object->id);		
		
    } 	
	
    /**
     * Добавляет произвольный материал в итератор
     *  
     * @param \Cetera\DynamicFieldsObject $material
     * @return void  
     */ 	
	public function add($material, $check = true) {
		if ($material->objectDefinition->id != $this->objectDefinition->id) {
			throw new \Exception('Illegal type of material '.$material->objectDefinition->id.'. Must be '.$this->objectDefinition->id);
		}
		$this->fetchElements();
		return parent::add($material, $check);
	}	
	
    /**
     * Удаляет материал из итератора
     *  
     * @param \Cetera\DynamicFieldsObject $material
     * @return void  
     */ 	
	public function remove(\Cetera\DynamicFieldsObject $material) {
		if ($material->objectDefinition->id != $this->objectDefinition->id) {
			throw new \Exception('Illegal type of material '.$material->objectDefinition->id.'. Must be '.$this->objectDefinition->id);
		}
		$this->fetchElements();
		foreach ($this->elements as $key => $value) {
			if ($value->id == $material->id) {
				unset($this->elements[$key]);
				$this->elements = array_values($this->elements);
				break;
			}
		}
		return $this;
	}	

}