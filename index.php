<?php
declare(strict_types=1);

const MAX_MOVES = 4;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


function getPokemon(string $input): array
{
    return json_decode(file_get_contents("https://pokeapi.co/api/v2/pokemon/" . $input), true);
}

function displayPicture(array $pokemon): string
{
    $imgUrl = $pokemon['sprites']['front_default'];
    return '<img id=img-pokemon src="' . $imgUrl . '" alt="pokemon">';
}

function displayMoves(array $pokemon): string
{
    $html = '<h4>Moves</h4>
                    <div id="get-moves">';

    $movesArray = $pokemon['moves'];

    if (count($movesArray) == 0) {
        $html .= 'This pokemon has no moves';
    } else {
        shuffle($movesArray);
        $moves = array_slice($movesArray, 0, MAX_MOVES);

        foreach ($moves as $move) {
            $html .= '<p>' . ucfirst($move['move']['name']) . '</p>';
        }
    }

    $html .= '</div>';

    return $html;
}

function flatten($array, $prefix = ''): array
{
    $flat = array();
    $sep = ".";

    foreach ($array as $key => $value) {
        $_key = ltrim($prefix . $sep . $key, ".");

        if (is_array($value) || is_object($value)) {
            $flat = array_merge($flat, flatten($value, $_key));
        } else {
            $flat[$_key] = $value;
        }
    }
    return $flat;
}

function displayEvolutionPicture($evolutionName)
{
    $pokemonToDisplay = getPokemon($evolutionName);
    $src = $pokemonToDisplay['sprites']['front_default'];
    return '<a href="index.php?pokemon-id=' . $evolutionName . '&find-pokemon=GO%21" id=' . $evolutionName . ' title="' . $evolutionName . '">
                    <img src="' . $src . '" alt="evolution">
                  </a>';
}

function findEvolutionsAndDisplayThem(array $pokemon)
{
    $species = json_decode(file_get_contents($pokemon['species']['url']), true);
    $evolutionChain = json_decode(file_get_contents($species['evolution_chain']['url']), true);

    if (count($evolutionChain['chain']['evolves_to']) >= 1) {

        $html = '<h4>Evolutions</h4>
                        <div id="evolution-images">';

        $flattenedChain = flatten($evolutionChain, $prefix = ' ');
        $filteredChain = array_filter($flattenedChain, function ($v, $k) {
            if (str_contains($k, 'species.name') && !str_contains($k, 'trade')) {
                return $v;
            }
        }, ARRAY_FILTER_USE_BOTH);

        $reversedArray = array_reverse($filteredChain);

        foreach ($reversedArray as $arrayItem) {
            if (is_string($arrayItem) && !empty($arrayItem)) {

                $pokemonObject = getPokemon($arrayItem);

                if ($pokemonObject != null) {
                    $pokemonName = $pokemonObject['species']['name'];
                    $html .= displayEvolutionPicture($pokemonName);
                }
            }
        }
        $html .= '</div>';
        return $html;
    }
}

$input = isset($_GET['pokemon-id']) ? $_GET['pokemon-id'] : 'eevee';
$pokemon = getPokemon($input);
$pokeName = $pokemon['name'];
$id = $pokemon['id'];


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src=""></script>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
          rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <title>Pokedex</title>
</head>
<body>

<img id="logo" src="imgs/pokedex.png" alt="pokedex-logo">
<div class="container">
    <div class="search-wrapper">
        <div class="input-container">
            <label for="pokemonId"></label>
            <form method="GET" action="">
                <input type="text" name="pokemon-id" id="pokemon-id" placeholder="Pokemon ID or Name"/>
                <input class="button" type="submit" name="find-pokemon" value="GO!">
            </form>
        </div>
    </div>
    <div id="tpl-pokemon">
        <ul>
            <div class="pokemon-wrapper">
                <li class="pokemon">
                    <h4 class="title">
                        <em class="ID-number">
                            <?php echo $id ?>
                        </em>
                        <strong class="name">
                            <?php echo ucfirst($pokeName) ?>
                        </strong>
                    </h4>
                    <?php
                    echo displayPicture($pokemon);
                    ?>
                </li>
                <li id="moves">
                    <?php
                    echo displayMoves($pokemon);
                    ?>
                </li>
                <div class="right-container">
                    <li id="all-evolutions">
                        <?php
                        echo findEvolutionsAndDisplayThem($pokemon)
                        ?>
                    </li>
                </div>
            </div>
        </ul>
    </div>
</div>

</body>
</html>