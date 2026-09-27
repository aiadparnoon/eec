<style id="print-style">
    @page {
        size: 8.3in 11.7in;
        margin: 0;
    }
</style>

<div class="card">
    <h5 class="card-header heading-color">گزارش مالی <?= $examDetail->title['fa'] ?></h5>
    <div class="table-responsive text-nowrap">
        <?php
        $i = 1;
        ?>
        <table class="table">
            <thead>
                <tr class="text-nowrap">
                    <th>#</th>
                    <th>نام و نام خانوادگی</th>
                    <th>کد ملی</th>
                    <th>نام پدر</th>
                    <th>شماره داوطلبی</th>
                    <th>تاریخ پرداخت</th>
                    <th>شماره سفارش</th>
                    <th>شماره مرجع</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                <?php
                $k = 1;
                foreach ($dataProvider->models as $order) {
                    $edit = 'edit' . rand();
                    $editScore = 'score' . rand();
                    $viewShare = 'viewShare' . rand();
                    $delete = 'delete' . rand();
                    $userDetail = $this->context->user_detail($order->username);
                    $score = null;
                    $row = null;
                    $statusIcon = 'badge bg-label-success';
                    if ($order->orders != null) {
                        $i = 0;
                        foreach ($order->orders as $item) {
                            if ($item['type'] == '3' && $item['_id'] == (string) $examDetail->_id)
                                $row = $i;
                            $i++;
                        }
                    }
                    if ($row !== null && $order->status == '1') {
                        if (array_key_exists('score', $order->orders[$row])) {
                            if ($order->orders[$row]['score'] != null && $order->orders[$row]['score'] != '')
                                $score = $order->orders[$row]['score'];
                        }
                        if (array_key_exists('removed', $order->orders[$row])) {
                            if ($order->orders[$row]['removed'] == '1')
                                $statusIcon = 'badge bg-label-danger';
                        }
                    }
                ?>
                    <tr>
                        <th scope="row"><?= $dataProvider->pagination->page * 10000 + $k++ ?></th>
                        <td>
                            <?php
                            if ($userDetail->applicant_info != null) {
                                if (array_key_exists('first_name', $userDetail->applicant_info))
                                    echo $userDetail->applicant_info['first_name'];
                                if (array_key_exists('last_name', $userDetail->applicant_info))
                                    echo ' ' . $userDetail->applicant_info['last_name'];
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($userDetail->applicant_info != null) {
                                if (array_key_exists('id', $userDetail->applicant_info))
                                    echo $userDetail->applicant_info['id'];
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($userDetail->applicant_info != null) {
                                if (array_key_exists('father_name', $userDetail->applicant_info))
                                    echo $userDetail->applicant_info['father_name'];
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $row = null;
                            if ($order->orders != null) {
                                $i = 0;
                                foreach ($order->orders as $item) {
                                    if ($item['type'] == '3' && $item['_id'] == (string) $examDetail->_id)
                                        $row = $i;
                                    $i++;
                                }
                            }
                            if ($row !== null && $order->status == '1') {
                                if (array_key_exists('applicant_id', $order->orders[$row]))
                                    echo $order->orders[$row]['applicant_id'];
                                else
                                    echo '-';
                            } else
                                echo '-';
                            ?>
                        </td>
                        <td><?= $order->payment_info['date'] ?></td>
                        <td><?= $order->payment_info['order_id'] ?></td>
                        <td><?= $order->payment_info['reference_id'] ?></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    window.onload = function() {
        window.print();
    };

    // Redirect to the previous page after printing or canceling
    window.onafterprint = function() {
        window.history.back();
    };
</script>