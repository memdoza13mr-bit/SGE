<?php
include("../config/conexion.php");

$id = $_GET['id'];

$conexion->query("DELETE FROM espacios WHERE id = $id");

header("Location: espacios.php");
?>