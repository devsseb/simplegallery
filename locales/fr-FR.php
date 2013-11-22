<?

$locale = array(
	'valid' => 'Valider',
	'add' => 'Ajouter',
	'apply' => 'Appliquer',
	'confirm-delete' => 'La suppression est permanente. Voulez-vous continuer ?',

	'structure' => array(
		'back' => 'Retour',
		'menu' => array(
			'configure-album' => 'Configurer l\'album',
			'manage-albums' => 'Gérer les albums',
			'analyze-selected-albums' => 'Analyzer les albums sélectionnés',
			'synchronize-albums-structure' => 'Synchroniser la structure des albums',
			'manage-users' => 'Gérer les utilisateurs',
			'show-deleted-medias' => 'Montrer les médias supprimés',
			'parameters' => 'Paramètres',
			'my-profile' => 'Mon profil',
			'logout' => 'Me déconnecter'
		)
	),
	
	'album' => array(

		'analyzer' => array(
			'js' => array(
				'thumbnail-generated' => 'Miniature "%1" générée',
				'thumbnail-updated' => 'Miniature "%1" mise à jour',
				'cover-generated' => 'Couverture générée',
				'cover-updated' => 'Couverture mise à jour',
				'medias-analyzed' => 'Médias analysés',
				'medias-synchronized' => 'Médias syncrhonisés',
				'synchronize-medias-selected' => 'Synchroniser les médias sélectionnés',
				'no-media-found' => 'Aucun média trouvé'
			)
		),

		'config' => array(
			'title' => 'Configuration de l\'album %1',
			'name' => 'Nom',
			'disable-comments' => 'Désactiver les commentaires',
			'i' => 'H',
			'inherit' => 'Hérite',
			'f' => 'I',
			'forbidden' => 'Interdit',
			'g' => 'A',
			'granted' => 'Autorisé',
			'group' => 'Groupe',
			'access-granted-default' => 'L\'accès est autorisé par défaut',
			'access-forbidden-default' => 'L\'accès est interdit par défaut',
			'access-granted' => 'L\'accès est autorisé',
			'access-forbidden' => 'L\'accès est interdit'
		),

		'index' => array(
			'no-media' => 'Aucun média',
			'no-album-or-media' => 'Aucun média ou album',
			'tools' => array(
				'rotate-left' => 'Tourner à gauche',
				'rotate-right' => 'Tourner à droite',
				'delete' => 'Supprimer',
				'restore' => 'Restaurer'
			),
			'extra' => array(
				'exif' => 'Donnée Exif',
				'comments' => 'Commentaires',
				'add-comment' => 'Ajouter un commentaire'
			),
			'js' => array(
				'delete' => 'Supprimer',
				'restore' => 'Restaurer',
				'exif' => array(
					'size' => 'Taille',
					'date' => 'Date',
					'camera' => 'Appreil',
					'dimension' => 'Dimension',
					'flash' => 'Flash',
					'yes' => 'Oui',
					'no' => 'Non'
				)
			)
		),

		'synchronizer' => array(
			'js' => array(
				'albums-synchronized' => 'Albums synchronisés',
				'back' => 'retour'
			)
		)
	),
	'install' => array(
		'index' => array(
			'title' => 'Installation',
			'medias-path' => 'Chemin des médias',
			'thumbnails-path' => 'Chemin des miniatures',
			'database-dsn' => 'DSN de la base de données',
			'database-user' => 'Utilisateur de la base de données',
			'database-passwprd' => 'Mot de passe de la base de données',
			'install' => 'Installer'
		)
	),
	'parameter' => array(
		'index' => array(
			'title' => 'Paramètres',
			'gallery-name' => 'Nom de la galerie',
			'disable-registration' => 'Désactiver l\'inscriptions',
			'disable-comments' => 'Désactiver les commentaires'
		)
	),
	
	'user' => array(
		'name' => 'Nom',
		'email' => 'Email',
		'password' => 'Mot de passe',
		'password-verification' => 'Vérification du mot de passe',
		
		'authentication' => array(
			'title' => 'Authentification',
			'keep-connection' => 'Garder la connexion',
			'lost-password' => 'Mot de passe perdu',
			'registration' => 'Inscription'
		),
		
		'lost-password' => array(
			'title' => 'Mot de passe perdu'
		),
		
		'management' => array(
			'groups' => array(
				'title' => 'Groupes',
				'add' => 'Ajouter un groupe',
				'delete' => 'Supprimer'
			),
			'user-add' => array(
				'title' => 'Ajouter un utilisateur'
			),
			'users' => array(
				'title' => 'Utilisateurs',
				'is-admin' => 'Est admin',
				'groups' => 'Groupes',
				'delete' => 'Supprimer'
			)
		),
		
		'profile' => array(
			'title' => 'Mon profil',
			'email-awaiting-validation' => 'Email en attente de validation',
			'leave-password-blank' => 'Laissez le mot de passe vide pour ne pas le mettre à jour'
		),
		
		'registration' => array(
			'title' => 'Inscription'
		)
	)
);

