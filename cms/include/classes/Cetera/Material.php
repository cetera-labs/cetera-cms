<?php
/**
 * Cetera CMS 3 
 *
 * @package CeteraCMS
 * @version $Id$
 * @copyright 2000-2010 Cetera labs (http://www.cetera.ru) 
 * @author Roman Romanov <nicodim@mail.ru> 
 **/
 
namespace Cetera; 
 
/**
 * Материал
 *   
 * @package CeteraCMS
 **/
class Material extends DynamicFieldsObject implements SiteItem {

    /**
     * Раздел, в котором находится материал 
     *         
     * @var int   
     */ 
    protected $_idcat;
    
    /**
     * Алиас материала 
     *         
     * @var string    
     */ 
    protected $_alias;
		
    protected $_published;
	protected $_show_future;
	
	private $_nearest = array();
        	
    /**
     * Возвращает материал по ID и типу (или таблице)
     *   
     * @param int $id ID материала        
     * @param int $type тип материала
     * @param string $table Таблица БД, в которой хранятся поля материала          
     * @return Material    
     */ 
	public static function getById($id, $type = 0, $table = null)
    {
        if ($type instanceof ObjectDefinition)
            $od = $type;
            else $od = new ObjectDefinition($type, $table);

        return parent::getByIdType($id, $od);
    }
    
    public function setFields($fields)
    {
        if (isset($fields['catalog_id'])) {
            $this->_idcat = $fields['catalog_id'];
            unset($fields['catalog_id']);
        }
        if (isset($fields['alias'])) {
            $this->_alias = $fields['alias'];
            unset($fields['alias']);
        }  
		if (isset($fields['type'])) {		
			$this->_published = $fields['type'] & MATH_PUBLISHED ? true : false;
			$this->_show_future = $fields['type'] & MATH_SHOW_FUTURE ? true : false;
		}
        return parent::setFields($fields);    
    }    
    
    /**
     * Возвращает раздел, которому принадлежит материал 
     * или false, если материал не принадлежит разделу
     *          
     * @return Catalog    
     */ 
    public function getCatalog()
    {
        if ($this->idcat < 0) return false;
        try {
            return Catalog::getById($this->idcat);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Возвращает описание полей объекта
     *         
     * @return array    
     */ 
    public function getFieldsDef()
    {
        return $this->objectDefinition->getFields($this->getCatalog());
    }     
    
    /**
     * Возвращает абсолютный URL материала
     *           
     * @return string
     */ 
    public function getUrl()
    {
        if ($this->idcat < 0) return false;     
        $url = '/'.$this->_alias;             
        if ( $this->getCatalog()->isServer() ) return $url;                   
        return rtrim($this->getCatalog()->getUrl(),'/').$url;
    } 
    
    /**
     * Возвращает полный URL материала
     *           
     * @return string
     */ 
    public function getFullUrl($prefix = TRUE)
    {
        if ($this->idcat < 0) return false;
        if (!$this->getCatalog()) return false;
        return $this->getCatalog()->getFullUrl($prefix).'/'.$this->_alias;
	}
	
    public function getBoUrl( $short = false )
    {
		if ($this->catalog)
		{
			$path = '';
			if (!$short) $path = $this->catalog->getFullUrl(false).'/';
			if (strlen($path)>50) $path = substr($path,0,20).'...'.substr($path,-20);
			$text = '<a title="'.$this->name.'" href="javascript:Cetera.getApplication().openBoLink(\'catalog:'.$this->getCatalog()->getTreePath().'$material:'.$this->id.'\')">'.
					$path.$this->name.
					'</a>';		
		}
		else
		{
			$text = $this->name;	
		}
		
		if (!$short) $text = '<span style="color:#999">'.$this->objectDefinition->description.':</span> '.$text;
		
		return $text;	
    }

    /**
     * Удаляет материал
     * 
     * @return void          
     */  
    public function delete()
    {
        parent::delete();
        
        $tpl = new Cache\Tag\Material($this->table, $this->id);
        $tpl->clean();
        
        $tpl = new Cache\Tag\Material($this->table, 0);
        $tpl->clean();
    }
    
    /**
     * Копирует материал в другой раздел
     * @param Catalog|int $dst раздел, куда копировать материал  
     * @return int ID нового материала
     * @throws Exception             
     */  
    public function copy($dst)
    {
        if ((int)$dst && $dst != -1) {
            $dst = Catalog::getById($dst);
        } elseif (!is_object($dst) && !is_int($dst)) {
            throw new Exception\CMS( '$dst must be a Catalog instance or catalog_id.');
        }
        
        if (is_object($dst)) {
            if ($dst->materialsType != $this->type)
                throw new Exception\CMS( 'Materials types of SRC and DST catalogs must be the same.' );
            $dst = $dst->id;
        }
        
        $table = $this->table;
        
    	$r = $this->getDbConnection()->query('SELECT name, type, len, pseudo_type FROM types_fields WHERE id='.$this->type);
    	$fields = array();
    	$flds = array();
    	$hlinks = array();
    	$fld_types = array();
    	while($f = $r->fetch()) {
    	    $fld_types[$f['name']] = $f['type'];
    		if (($f['type'] != FIELD_LINKSET)&&($f['type'] != FIELD_MATSET)) {
    		    $flds[] = $f['name'];
    			if ($f['type'] == FIELD_HLINK) $hlinks[] = $f['name'];
    		} 
			else {
    			$fields[] = $f;
    		}
    	} // while
    	
    	$values = $this->getDbConnection()->fetchAssoc('SELECT '.implode(',', $flds).' FROM '.$table.' WHERE id='.$this->id);
    	if (!$values) return FALSE;
    	
    	$values['idcat'] = $dst;
    	$values['tag'] = $this->getDbConnection()->fetchColumn("SELECT MAX(tag) FROM $table WHERE idcat=".$dst) + 1;
    	
    	if ($dst >= 0) {
            $r = $this->getDbConnection()->fetchColumn('SELECT COUNT(*) FROM '.$table.' WHERE idcat=? and alias=?', array($dst, $values['alias']));
            if ($r) {
                $values['alias'] .= '_copy';
                $alias = $values['alias'];
                $alias_exists = 1;
                $i = 1;
                while($alias_exists) {
                    if ($i > 1) $values['alias'] = $alias.'_'.$i;
                    $alias_exists = $this->getDbConnection()->fetchColumn('SELECT COUNT(*) FROM '.$table.' WHERE idcat=? and alias=?', array($dst, $values['alias']));
                    $i++;
                }
            }
        }
    	
    	foreach ($hlinks as $hlink) {
    		$f = $this->getDbConnection()->fetchAssoc('SELECT * FROM field_link WHERE link_id='.(int)$values[$hlink]);
    		if ($f) {
    			unset($f['link_id']);
    			$this->getDbConnection()->executeQuery('INSERT INTO field_link ('.implode(',', array_keys($f)).') VALUES ("'.implode('","', array_values($f)).'")');
    			$values[$hlink] = $this->getDbConnection()->lastInsertId();
    		}
    	}
    	
    	foreach($values as $no => $value) {
    	   if ($fld_types[$no] == FIELD_DATETIME && !$value) $values[$no] = "'0000-00-00 00:00:00'";
             else $values[$no] = $this->getDbConnection()->quote($value);
        }
    	reset($values);
    	
    	$this->getDbConnection()->executeQuery('INSERT INTO '.$table.' ('.implode(',', $flds).') VALUES ('.implode(',', $values).')');
    	$newid = $this->getDbConnection()->lastInsertId();
    	
    	foreach ($fields as $field)
		{			
    		if ($field['type'] == FIELD_LINKSET) {
				
				if ($field['len'] == CATALOG_VIRTUAL_USERS) {
					$tablel = User::TABLE;
				}
				elseif ($field['pseudo_type'] == PSEUDO_FIELD_CATOLOGS) {
					$tablel = Catalog::TABLE;        
			    } 
				elseif (!$field['len']) {
					$tablel = $this->table;
				}
				else {
				    $c = Catalog::getById($field['len']);
				    if (!$c) continue;
				    $tablel = $c->materialsTable;
			    }								
				
    			$r = $this->getDbConnection()->query('SELECT dest, tag FROM '.$table.'_'.$tablel.'_'.$field['name'].' WHERE id='.$this->id);
    			while($f = $r->fetch()){
    				$this->getDbConnection()->executeQuery('INSERT INTO '.$table.'_'.$tablel.'_'.$field['name'].' (id, dest, tag) VALUES ('.$newid.','.$f['dest'].','.$f['tag'].')');
    			} // while
    		} 
			else
			{
    			$tablel = $this->getDbConnection()->fetchColumn('SELECT alias FROM types WHERE id='.(int)$field['len']);
    			if (!$tablel) continue;	
    			$r = $this->getDbConnection()->query('SELECT dest, tag FROM '.$table.'_'.$tablel.'_'.$field['name'].' WHERE id='.$this->id);
    			while($f = $r->fetch()) {
    			    $m = Material::getById($f['dest'], $field['len'], $tablel);
    			    if ($m) {
        				$newdest = $m->copy(-1);
        				if ($newdest) {
        					$this->getDbConnection()->executeQuery('INSERT INTO '.$table.'_'.$tablel.'_'.$field['name'].' (id, dest, tag) VALUES ('.$newid.','.$newdest.','.$f['tag'].')');
						}
    			    }
    			} // while
    		}
    	}
    				
    	return $newid;

    }
    
    /**
     * Очистить все кэши связанные с этим материалом     
     *          
     * @return void  
     */ 
    protected function updateCache()
    {
        $tpl = new Cache\Tag\Material($this->table,$this->id);
        $tpl->clean();
    
        $tpl = new Cache\Tag\Material($this->table,0);
        $tpl->clean();
    }
    
    public function save($hidden = true, $unique_alias = false)
    {        
        $this->getFieldsDef();
        
		$type = $this->raw_fields['type'];

        if ($this->fields['publish']) $type = $type | MATH_PUBLISHED;
		if ($this->fields['show_future']) $type = $type | MATH_SHOW_FUTURE; else $type = $type & ~MATH_SHOW_FUTURE;
        
        if (isset($this->fieldsDef['tag']) && !$this->fields['tag'])
		{
			$sql = 'SELECT MAX(tag) FROM '.$this->table;
			if ($this->idcat >= 0)
			{
				$sql .= ' WHERE idcat='.$this->idcat;
			}
			$r = mysql_query($sql);
			if ($r && mysql_num_rows($r)) $this->fields['tag'] = mysql_result($r,0)+100; else $tag = 100;
		}
		
		$generateAlias = false;
        if ($this->idcat >= 0) {
            if (!$this->alias) {
              	$r = fssql_query("SELECT tablename,type from dir_data where id=".$this->idcat);
              	if (mysql_num_rows($r)) list($catname, $t) = mysql_fetch_row($r);
            
              	if ($t & Catalog::AUTOALIAS) {
              		if ($t & Catalog::AUTOALIAS_ID) {
						if ($this->id) {
							$this->_alias = $this->id;
						}
						else {
							$generateAlias = true;
						}
					}					
              		elseif ($t & Catalog::AUTOALIAS_TRANSLIT) {
						$this->_alias = strtolower(translit($_POST['name']));
					}
              	    else {
						$this->_alias = date('YmdHis');
					}
              	}
        	
        	    $this->_alias = substr($this->alias,0,255);
            }
        	
			$orig_alias = $this->alias;
			$i = 2;
			do
			{
				$sql = 'SELECT COUNT(*) FROM '.$this->table.' WHERE alias="'.mysql_escape_string($this->alias).'" and idcat='.$this->idcat;
				if ($this->id) $sql .= ' and id<>'.$this->id;
				$r = fssql_query($sql);
				$found = mysql_result($r, 0);
				if ($found && !$unique_alias) throw new Exception\Form(Exception\CMS::ALIAS_EXISTS, 'alias');			
				if ($found) $this->alias = $orig_alias.'_'.$i++;
			} 
			while ($found);

        }
        
        if (!$this->id)
		{
            if ($this->idcat == CATALOG_VIRTUAL_HIDDEN) $type = $type | MATH_ADDED | MATH_PUBLISHED;
        }
        
        $author = json_decode( $this->fields['autor'] );
        if ( is_object($author) ) $this->fields['autor'] = $author->id;
        
        $values = 'alias="'.$this->alias.'", name="'.mysql_escape_string($this->fields['name']).'",idcat='.$this->idcat.',autor='.(int)$this->fields['autor'].",type=$type";
        
        $values .= $this->saveDynamicFields(array('name', 'alias', 'idcat', 'autor'), $hidden);
             
        if ($this->id) {
        
            $sql = "UPDATE ".$this->table." SET ".$values." WHERE id=".$this->id;
            
        } else {
        
            $sql = "INSERT INTO ".$this->table." SET ".$values;
            
        }
        fssql_query($sql);
        
		if (!$this->id) {
			$this->_id = mysql_insert_id();  
			
			if ($generateAlias) {
				$this->_alias = $this->id;
				self::getDbConnection()->update($this->table, array('alias' => $this->alias), array('id' => $this->id) );
			}
		}
        
        if ($this->idcat > 0) {
            $application = Application::getInstance();
            if (!$this->id) {
                $application->eventLog(EVENT_MATH_CREATE, $this->getBoUrl());
            } else {
                $application->eventLog(EVENT_MATH_EDIT, $this->getBoUrl());
              
            }
            if ($this->fields['publish']) $application->eventLog(EVENT_MATH_PUB, $this->getBoUrl());
        }
      
        $this->saveDynimicLinks();
        $this->updateCache();
   
    }
    
    public function __set($name, $value)
    {
        if ($name == 'idcat') {
        
            $this->_idcat = $value;
            $this->fields['idcat'] = $value;
        
        } elseif ($name == 'alias') {
        
            $this->_alias = $value;
            $this->fields['alias'] = $value;        
        
        } else {
        
            parent::__set($name, $value);
            
        }
    }  

	public function getNext($field = 'dat')
	{
		return $this->getNearest($field, 'ASC');	
	}

	public function getPrev($field = 'dat')
	{
		return $this->getNearest($field, 'DESC');	
	}

	private function getNearest($field, $sort)	
	{
		if (!isset($this->_nearest[$field.$sort]))
		{
			$cond = '>';
			if (strtoupper($sort) == 'DESC') $cond = '<';
			
			$c = $this->getCatalog();
			if ($c)
			{
				$list = $c->getMaterials();
			}
			else
			{
				$list = $this->objectDefinition->getMaterials();
			}
			$list->where('`'.$field.'` '.$cond.' "'.$this->$field.'"')->orderBy($field, $sort)->setItemCountPerPage(1);
			if ($list->count()) 
			{
				$this->_nearest[$field.$sort] = $list->current();
			}
			else
			{
				$this->_nearest[$field.$sort] = false;
			}		
		}
		return $this->_nearest[$field.$sort];
	}
}