<?php

/**
 * Class Usuario
 * @Entity
 * @Table(name="usuario")
 */
class Usuario extends \Osians\dal\Model
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    
    /**
     * @Column(type="string",name="name",length=32)
     */
    protected $name;
    
    /**
     * @Column(type="string",name="last_name",length=32)
     */
    protected $active;

    /**
     * @Column(type="string",unique=true)
     */
    protected $email;
}