<?php

require_once __DIR__ . '/../models/BoutiqueAchat.php';
require_once __DIR__ . '/../models/BoutiqueVente.php';
require_once __DIR__ . '/../models/BoutiqueArticle.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/ParamLycee.php';
require_once __DIR__ . '/../core/Validator.php';

class BoutiqueAchatController {

    private function checkAccess() {
        if (!Auth::can('manage', 'boutique')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($eleve_id);
        $achats = BoutiqueAchat::findByEleveId($eleve_id);
        require_once __DIR__ . '/../views/boutique/achats/index.php';
    }

    public function create() {
        $this->checkAccess();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($eleve_id);

        $lycee_id = Auth::get('role_name') === 'admin_local' ? Auth::get('lycee_id') : null;
        if (!$lycee_id && $eleve) {
            $lycee_id = $eleve['lycee_id'];
        }

        $articles = BoutiqueArticle::findAll($lycee_id);

        require_once __DIR__ . '/../views/boutique/shop/index.php';
    }

    public function recu() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /eleves');
            exit();
        }

        $vente = BoutiqueVente::findById($id);
        $items = BoutiqueVente::getItems($id);
        $lycee = ParamLycee::findByLyceeId($vente['lycee_id']);

        require_once __DIR__ . '/../views/boutique/achats/recu.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $eleve_id = $_POST['eleve_id'] ?? null;
            $cart_data = $_POST['cart_data'] ?? null;

            if ($eleve_id && $cart_data) {
                $cart = json_decode($cart_data, true);
                if (!empty($cart)) {
                    $eleve = Eleve::findById($eleve_id);
                    $total = 0;
                    foreach ($cart as $item) {
                        $total += $item['price'] * $item['quantity'];
                    }

                    try {
                        $vente_id = BoutiqueVente::create([
                            'eleve_id' => $eleve_id,
                            'lycee_id' => $eleve['lycee_id'],
                            'montant_total' => $total,
                            'cart' => $cart
                        ]);

                        // Redirect to receipt
                        header('Location: /boutique/recu?id=' . $vente_id);
                        exit();
                    } catch (Exception $e) {
                        // Handle error (e.g., stock issue)
                        die("Erreur lors de la validation : " . $e->getMessage());
                    }
                }
            }
        }
        header('Location: /eleves');
        exit();
    }
}
?>
