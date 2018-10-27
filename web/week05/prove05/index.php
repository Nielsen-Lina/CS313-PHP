<?php

try
{
  $dbUrl = getenv('DATABASE_URL');

  $dbOpts = parse_url($dbUrl);

  $dbHost = $dbOpts["host"];
  $dbPort = $dbOpts["port"];
  $dbUser = $dbOpts["user"];
  $dbPassword = $dbOpts["pass"];
  $dbName = ltrim($dbOpts["path"],'/');

  $db = new PDO("pgsql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPassword);

  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $ex)
{
  echo 'Error!: ' . $ex->getMessage();
  die();
} 

echo "<h1>Expense Management System</h1>";
echo "<h2>List of Budget Categories:</h2>";

$sql = 'SELECT category_id, category_name FROM budget ORDER BY category_name';
$stmt = $db->query($sql);
$rows = $stmt;


foreach ($rows as $row)
{
  echo "<a href='details.php?category_id=" . $row['category_id'] . "'>" . $row['category_name'] . "</a><br/>";
}

?>

<h2><a href='transactions.php'>List of Transactions</a></h2>
<h2>List of Companies for a chosen Category:</h2>
<form method="GET" action="index.php">
  <input type="text" name="category_name" placeholder="category name">
  <input type="submit" value="Search">
</form>

<?php

$category_name = htmlspecialchars($_GET['category_name']);
$sql_1 = 'SELECT category_id FROM budget WHERE lower(category_name)=lower(:category_name)';

$stmt = $db->prepare($sql_1);
$stmt->bindValue(':category_name', $category_name, PDO::PARAM_STR);
$stmt->execute();
$id = $stmt->fetch(PDO::FETCH_ASSOC);
$id = $id['category_id'];
//print_r($id);

$sql_2 = 'SELECT company_name FROM detail WHERE category_id=:category_id';
//$sql_2 = 'SELECT detail.company_name FROM budget JOIN detail ON budget.category_id=detail.category_id';
//$stmt = $db->query($sql_2);
//$names = $stmt;
$statement = $db->prepare($sql_2);
$statement->bindParam(':category_id', $id);
$statement->execute();
$names = $statement->fetchAll(PDO::FETCH_ASSOC);

echo "<ul>";
foreach ($names as $name)
{
  echo "<li>" . $name['company_name'] . "</li>";
}
echo "</ul>";

$stmt = $db->prepare('SELECT category_id, category_name, amount FROM budget');
$stmt->execute(array());
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$category_chk = [];

$stmtDetail = $db->prepare('SELECT detail_id, company_name, category_id FROM detail');
$stmtDetail->execute(array());
$details = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

$company_chk = [];

$stmtExpense = $db->prepare('SELECT expense.expense_id, detail.company_name, expense.transaction_amount, expense.purchase_date FROM detail JOIN expense ON detail.detail_id=expense.detail_id');
$stmtExpense->execute(array());
$expenses = $stmtExpense->fetchAll(PDO::FETCH_ASSOC);

$expense_chk = [];

?>

<br/>
<h2>Changes to the Expense Management System</h2>
<h3>Change a new budget category with its accompanied amount:</h3>
<form method="POST" action="add_budget.php">
  <input type="text" name="category_name" placeholder="category name">
  <input type="text" name="amount" placeholder="amount"><br>
  <input type="submit" value="Add"><br>
  <p>Select a category name in order to do the following:</p><br>
  <input type="submit" name="update_category" formaction="update_budget.php" value="Update Category"><br>
  <input type="submit" name="update_amount" formaction="update_budget.php" value="Update Amount"><br>
  <input type="submit" formaction="delete_budget.php" value="Delete"><br>
  <?php foreach ($rows as $row) : ?>
    <input type="checkbox" name="category_chk[]" value="<?= $row['category_id']; ?>"/><?php echo $row['category_name']; ?><br>
  <?php endforeach; ?>
</form>
<h3>Change a new company name with its accompanied budget category:</h3>
<form method="POST" action="add_company.php">
  <input type="text" name="company_name" placeholder="company name">
  <input type="text" name="category_name" placeholder="category name"><br>
  <input type="submit" value="Add"><br>
  <p>Select a company name in order to do the following:</p><br>
  <input type="submit" name="update_company" formaction="update_detail.php" value="Update Company"><br>
  <input type="submit" name="update_category" formaction="update_detail.php" value="Update Category"><br>
  <input type="submit" formaction="delete_detail.php" value="Delete"><br>
  <?php foreach ($details as $detail) : ?>
    <input type="hidden" name="category_id" value="<?= $detail['category_id']; ?>">
    <input type="checkbox" name="company_chk[]" value="<?= $detail['detail_id']; ?>"/><?php echo $detail['company_name']; ?><br>
  <?php endforeach; ?>
</form>
<h3>Change a new expense with its accompanied company name, amount and date of purchase:</h3>
<form method="POST" action="add_expense.php">
  <input type="text" name="company_name" placeholder="company name">
  <input type="text" name="transaction_amount" placeholder="transaction amount">
  <input type="text" name="purchase_date" placeholder="purchase date"><br>
  <input type="submit" value="Add"><br>
  <ul>
  <?php foreach ($expenses as $expense) : ?>
    <input type="checkbox" name="expense_chk[]" value="<?= $expense['expense_id']; ?>"/><?php echo $expense['company_name'] . " " . $expense['transaction_amount'] . " " . $expense['purchase_date'] ?></li><br>
  <?php endforeach; ?>
  </ul>
  <input type="submit" name="update_company_name" formaction="update_budget.php" value="Update Company Name"><br>
  <input type="submit" name="update_transaction_amount" formaction="update_budget.php" value="Update Transaction Amount"><br>
  <input type="submit" name="update_date" formaction="update_budget.php" value="Update Purchase Date"><br>
  <input type="submit" formaction="delete_budget.php" value="Delete"><br>
</form>

