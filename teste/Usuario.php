<?php

use Osians\Dal\Model;

/**
 * Class Usuario
 * @Entity
 * @Table(name="usuario")
 */
class Usuario extends Model
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id_usuario;
    
    /**
     * @Column(type="string",name="name",length=32)
     */
    protected $nome;
    
    /**
     * @Column(type="string",name="last_name",length=32)
     */
    protected $email;

    /**
     * @Column(type="bool",unique=true)
     */
    protected $ativo;
}