<?php

/**
 * L'environnement sous forme d'entité, ne change pas en cours d'execution.
 * L'idée va être de faire une réflexion sur les entités avec leur annotations afin de cartographier notre environnement.
 * On va ensuite mettre en cache ce que l'on a trouvé pour pouvoir s'y référer lorsque l'on manipulera les entité.
 * On ne gagne jamais sur tous les tableaux, si on veut optimiser la vélocité, alors il faut consommer de la ram (mise en cache).
 * A l'inverse si on veut économiser la mémoire, alors il faut accepter une vélocité moindre. 
 */

class DiscoverDatabaseStructure
{
    public function __construct()
    {
        
    }

    /**
     * Pour découvrir notre environnement on doit d'abord parcourir l'ensemble de nos entités et comprendre les relations entres elles.
     * Une fois que l'on a réussi à faire ça, on doit organiser ça dans un cache de manière logique pour que si par exemple je cherche
     * une relation X dans l'entité Y, je sache :
     *   - Quel type de relation est ce : OneToMany, ManyToOne, ManyToMany, OneToOne
     *   - Quel est/sont la/les tables en jeu
     *   - Quels sont les champs dans chacunes d'entres elles que je dois consulter
     * On va architecturer ça sous forme de classes pour pas que ce soit trop fouilli.
     */
    public function discoverEntities()
    {
        
    }
}