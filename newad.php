<?php
  session_start();
  $pageTitle = 'Create New Item';

  include "init.php";

  if (isset($_SESSION['user'])) {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') :

      // Upload varialbles
      $imageName = $_FILES["image"]["name"];
      $imageType = $_FILES["image"]["type"];
      $imageTmp  = $_FILES["image"]["tmp_name"];
      $imageSize = $_FILES["image"]["size"];
      // Allower extensions
      $allowedExtensions = array("jpeg", "jpg", "png", "gif");
      // Get image extension
      $imageExtension = explode('.', $imageName);
      $imageExtension = strtolower(end($imageExtension));

      $formErrors = array();

      $name          = filter_var(trim($_POST["name"]), FILTER_SANITIZE_STRING);
      $description   = filter_var(trim($_POST["description"]), FILTER_SANITIZE_STRING);
      $price         = filter_var(trim($_POST["price"]), FILTER_SANITIZE_NUMBER_FLOAT);
      $country       = filter_var(trim($_POST["country"]), FILTER_SANITIZE_STRING);
      $status        = filter_var($_POST["status"], FILTER_SANITIZE_NUMBER_INT);
      $category      = filter_var($_POST["category"], FILTER_SANITIZE_NUMBER_INT);
      $tags          = filter_var(trim($_POST["tags"]), FILTER_SANITIZE_STRING);

      if (strlen($name) < 4) { $formErrors[] = "Item name can't be less than 4 characters."; }
      if (strlen($description) < 10) { $formErrors[] = "Item description can't be less than 10 characters."; }
      if (strlen($country) < 2) { $formErrors[] = "Country can't be less than 2 characters."; }
      if (empty($price)) { $formErrors[] = "Price can't be empty."; }
      if (empty($status)) { $formErrors[] = "Status can't be empty."; }
      if (empty($category)) { $formErrors[] = "Category can't be empty."; }
      if (empty($imageName)) { $formErrors[] = "Image is required."; }
      else if (!empty($imageName) && !in_array($imageExtension, $allowedExtensions)) { $formErrors[] = "This extension is not Allowed."; }
      else if ($imageSize > 4194304) { $formErrors[] = "Image can't be more than 4MB."; }


      // Check if there's no error, proceed the item add
      if (empty($formErrors)) :
        $image = md5(date('ymdHsiu') . $imageName . rand(0, 1000000));
        // check if another user has the same items name, if yes regenerate different name
        while (checkItem("image", "items", $image)) {
          $image = md5(date('ymdHsiu') . $imageName . rand(0, 1000000));
        }
        move_uploaded_file($imageTmp, __DIR__ . "\\uploads\\items\\" . $image . "." . $imageExtension);
        // add extension to items
        $image = $image . "." . $imageExtension;

        // Insert Item Info in Database
        $stmt = $con->prepare("INSERT INTO items(Name, Description, Price, Add_Date, Country_Made, Image, Status, CatID, MemberID, Tags)
                                VALUES(:name, :description, :price, now(), :country, :image, :status, :category, :member, :tags)");
        $stmt->execute(array(
          'name'            => $name,
          'description'     => $description,
          'price'           => $price,
          'country'         => $country,
          'status'          => $status,
          'image'           => $image,
          'category'        => $category,
          'member'          => $_SESSION['userid'],
          'tags'            => $tags
        ));

        // Echo success message
        if ($stmt) { $successMsg = "Item added successfully."; }
      endif;

    endif;
?>
    <h1 class="text-center"><?php echo $pageTitle ?></h1>
    <div class="create-item block">
      <div class="container">
        <div class="panel panel-primary">
          <div class="panel-heading"><?php echo $pageTitle ?></div>
          <div class="panel-body">
            <div class="row">
              <!-- Start creation form item -->
              <div class="col-md-8">
                <form class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" enctype="multipart/form-data">
                  <!-- Start Name -->
                  <div class="form-group form-group-lg">
                    <label class="col-sm-3 control-label">Name</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control live" data-class=".live-title" name="name" required pattern=".{4,}" title="Item name can't be less than 4 characters." placeholder="Item Name">
                    </div>
                  </div>
                  <!-- End Name -->

                  <!-- Start Description -->
                  <div class="form-group form-group-lg">
                    <label class="col-sm-3 control-label">Description</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control live" data-class=".live-description" name="description" required pattern=".{10,}" title="Item description can't be less than 10 characters." placeholder="Item Description">
                    </div>
                  </div>
                  <!-- End Description -->

                  <!-- Start Price -->
                  <div class="form-group form-group-lg">
                    <label class="col-sm-3 control-label">Price</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control live" data-class=".live-price" name="price" required placeholder="Item Price">
                    </div>
                  </div>
                  <!-- End Price -->

                  <!-- Start Country -->
                  <div class="form-group form-group-lg">
                    <label class="col-sm-3 control-label">Country</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control" name="country" required placeholder="Item manufacturing country">
                    </div>
                  </div>
                  <!-- End Country -->

                  <!-- Start Status -->
                  <div class="form-group form-group-lg">
                    <label class="col-sm-3 control-label">Status</label>
                    <div class="col-sm-8">
                      <select name="status" required>
                        <option value="">...</option>
                        <option value="1">New</option>
                        <option value="2">Like New</option>
                        <option value="3">Used</option>
                        <option value="4">Very Old</option>
                      </select>
                    </div>
                  </div>
                  <!-- End Status -->

                  <!-- Start Category -->
                  <div class="form-group form-group-lg">
                    <label class="col-sm-3 control-label">Category</label>
                    <div class="col-sm-8">
                      <select name="category" required>
                        <option value="">...</option>
                        <?php
                          $args = array("table" => "categories", "conditions" => array("parent" => 0), "orderBy" => "Name", "orderType" => "ASC");
                          foreach (getFrom($args) as $category) :
                            echo '<option value="' . $category['ID'] . '">' . $category['Name'] . '</option>';
                            $childCatArgs = array("table" => "categories", "conditions" => array("parent" => $category['ID']), "orderBy" => "Name", "orderType" => "ASC");
                            foreach (getFrom($childCatArgs) as $childCat) {
                              echo '<option value="' . $childCat['ID'] . '">&nbsp&nbsp;&nbsp;' . $childCat['Name'] . '</option>';
                            }
                          endforeach;
                        ?>
                      </select>
                    </div>
                  </div>
                  <!-- End Category -->

                  <!-- Start Tags -->
                  <div class="form-group form-group-lg">
                    <label class="col-sm-3 control-label">Tags</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control" name="tags" placeholder="Separate tags with comma (,)">
                    </div>
                  </div>
                  <!-- End Tags -->

                  <!-- Start Image -->
                  <div class="form-group form-group-lg">
                    <label class="col-sm-3 control-label">Image</label>
                    <div class="col-md-8">
                      <input type="file" class="form-control" name="image" required="required">
                    </div>
                  </div>
                  <!-- End Image -->

                  <!-- Start Submit -->
                  <div class="form-group form-group-lg">
                    <div class="col-sm-offset-3 col-sm-8">
                      <input type="submit" class="btn btn-primary btn-lg btn-block" value="Add Items">
                    </div>
                  </div>
                  <!-- End Submit -->
                </form>
              </div>
              <!-- End creation form item -->
              <!-- Start preview creation item -->
              <div class="col-md-4">
                <div class="thumbnail item-box live-preview">
                  <span class="item-price">
                    <span class="live-price">0</span>$
                  </span>
                  <img class="img-responsive" src="admin/uploads/items/default-item.png" alt="">
                  <div class="caption">
                    <h3 class="live-title">Item Title</h3>
                    <p class="live-description">Description</p>
                  </div>
                </div>
              </div>
              <!-- End preview creation item -->
            </div>
            <!-- Start looping through errors -->
            <?php
            if (!empty($formErrors)) {
              foreach ($formErrors as $error) {
                echo '<div class="alert alert-danger">' . $error . '</div>';
              }
            }

            if (isset($successMsg)) { echo '<div class="alert alert-success">' . $successMsg . '</div>'; }
            ?>
            <!-- End looping through errors -->
          </div>
        </div>
      </div>
    </div>
<?php
  } else {
    header("Location: login.php");
    exit();
  }
?>
<?php include $tpl . "footer.php"; ?>
