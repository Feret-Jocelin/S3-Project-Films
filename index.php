<?php
$host = "163.172.130.142:3310";
$dbname = "sakila";
$username = "etudiant";
$password = "CrERP29qwMNvcbnAMgLzW9CwuTC5eJHn";

try {
    // Connexion à la base de données/instance Sakila
    $dbConnection = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
} catch (PDOException $e) {
    // En cas d'erreur, on affiche un message et on arrête tout
    die("Impossible de se connecter à la base de données $dbname:" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>S3-Projet</title>
</head>
<body class="p-5">

<h1 class="mx-auto" style="width: 200px;">Films</h1>

<?php

// récupération de tous les films
$query = "SELECT DISTINCT film.title, film.rental_rate, film.rating, category.name, count(film.title) as rental
                  FROM film
                  JOIN film_category
                  ON film.film_id = film_category.film_id 
                  JOIN category
                  ON category.category_id = film_category.category_id
                  LEFT JOIN inventory
                  ON film.film_id = inventory.film_id
                  LEFT JOIN rental
                  ON inventory.inventory_id = rental.inventory_id";

$queryCount = "SELECT count(film.film_id) as count FROM film";
$params = [];
$sortable = ["film.title", "category.name", "rental"];

//Recherche par titre de film
if (!empty($_GET['q'])) {
    $query .= " WHERE film.title LIKE :title";
    $queryCount .= " WHERE film.title LIKE :title";
    $params['title'] = '%' . $_GET['q'] . '%';
}

//Pagination
$nbFilmPerPages = 20;
$page = isset($_GET["p"]) ? $_GET["p"] : 1;
$offset = ($page - 1) * $nbFilmPerPages;

$query .= " GROUP BY film.title";

//Organisation
if (!empty($_GET['sort']) && in_array($_GET['sort'], $sortable)) {
    $direction = isset($_GET['dir']) ? $_GET['dir'] : 'asc';
    if (!in_array($direction, ['asc', 'desc'])) {
        $direction = 'asc';
    }
    $query .= " ORDER BY " . $_GET['sort'] ." $direction";
}

//limite de films par pages
if (isset($_GET['sort'])) {
    $query .= " limit $nbFilmPerPages OFFSET $offset;";
} else {
    $query .= " ORDER BY film.title limit $nbFilmPerPages OFFSET $offset;";
}


$statement = $dbConnection->prepare($query);
$statement->execute($params);
$response = $statement;

//compte le nombre de pages
$statementCount = $dbConnection->prepare($queryCount);
$statementCount->execute($params);
$count = $statementCount->fetch()['count'];
$nbPages = ceil($count / $nbFilmPerPages);

?>
<!-- Barre de recherche -->
<form action="" class="mb-4" style="width: 400px">
    <div class="form-group">
        <input type="text" class="form-control" name="q" placeholder="Rechercher par nom de film"
               value="<?= htmlentities(isset($_GET['q']) ? $_GET['q'] : null) ?>">
    </div>
    <button class="btn btn-primary">Rechercher</button>
</form>

<table class="table table-striped">
    <thead>
    <tr>
        <th>
            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => "film.title",
                'dir' => ((isset($direction) && $direction == "asc") && $_GET['sort'] == "film.title") ? 'desc' : 'asc']));?>">Nom</a>
        </th>
        <th>Prix de location</th>
        <th>Classement</th>
        <th>
            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => "category.name",
                'dir' => ((isset($direction) && $direction == "asc") && $_GET['sort'] == "category.name") ? 'desc' : 'asc']));?>">Genre</a>
        </th>
        <th>
            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => "rental",
                'dir' => ((isset($direction) && $direction == "asc") && $_GET['sort'] == "rental") ? 'desc' : 'asc']));?>">Nombre de location</a>
        </th>
    </tr>
    </thead>
    <?php

    while ($data = $response->fetch()) {
        printf("<tr><td>%s</td><td>%s €</td><td>%s</td><td>%s</td><td>%s</td></tr>",
            $data['title'], $data['rental_rate'], $data['rating'], $data['name'], $data['rental']);
    }

    ?>

</table>

<nav class="pagination justify-content-center">
    <ul class="pagination pagination-sm">

        <!-- Page précédente -->
        <?php if ($nbPages > 1 && $page > 1): ?>
            <li class="page-item">
                <a href="?<?= http_build_query(array_merge($_GET, ['p' => $page - 1])); ?>" class="page-link">Précédent</a>
            </li>
        <?php endif; ?>

        <!-- Numéros de page -->
        <?php for ($i = 1; $i <= $nbPages; $i++) :?>
            <li class="page-item" aria-current="<?=$page?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $i])); ?>"><?=$i?></a></li>
        <?php endfor; ?>

        <!-- Page suivante -->
        <?php if ($nbPages > 1 && $page < $nbPages): ?>
            <li class="page-item">
                <a href="?<?= http_build_query(array_merge($_GET, ['p' => $page + 1])); ?>" class="page-link">Suivant</a>
            </li>
        <?php endif; ?>

    </ul>
</nav>

</body>
</html>