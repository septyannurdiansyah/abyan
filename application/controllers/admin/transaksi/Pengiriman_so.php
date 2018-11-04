<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Pudak Digital
 * Date: 5/31/2017
 * Time: 1:14 PM
 */


/**
 * @property  ion_auth $ion_auth
 * @property  barang_model $barang_model
 * @property  datatables $datatables
 * @property  barang_keluar_model $barang_keluar_model
 * @property  barang_masuk_model $barang_masuk_model
 * @property  rak_model $rak_model
 * @property  stok_fisik_model $stok_fisik_model
 */
class Pengiriman_so extends Admin_Controller
{

    function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->in_group('admin')) {
            redirect('auth/session_not_authorized', 'refresh');
        }
        $this->load->library('form_validation');
        $this->load->helper('text');
        $this->load->helper('url');
        $this->load->model('barang_model');
        $this->load->model('pengiriman_so_model');
        $this->load->model('sales_order_model');
        $this->load->model('stok_model');
        $this->load->model('penerimaan_po_model');
        $this->load->model('piutang_model');
         
    }

    public function index()
    {
        //$this->data['pilihan_barang'] = $this->barang_model->get_all();
        //$this->data['pilihan_rak'] = $this->rak_model->get_all();
        //$this->data['pilihan_gudang'] = $this->gudang_model->get_all();
        $this->render('admin/transaksi/Pengiriman_so_view');
    }

    public function get_nobukti()
    {

        $no_bukti = $this->stok_fisik_model->get_nobukti();

        echo json_encode($no_bukti);
    }

    public function get_data_all(){

        $list = $this->pengiriman_so_model->get_datatables();
        $data = array();
        $no = $this->input->post('start');
        foreach ($list as $dt) {
            $no++;
            $row = array();
            $row[] = $no;
            $row[] = $dt->id;
            $row[] = $this->tanggal($dt->tanggal);
            $row[] = $dt->kode_pengiriman;
            $row[] = $dt->kode_so;
            $row[] = $dt->kode_kurir;
            $row[] = $dt->nama_kurir;
            $row[] = $dt->qty;
            $row[] = $dt->keterangan;
            $row[] = '<a class="btn btn-sm btn-success" href="javascript:void(0)" title="Edit" onclick="bayar_hutang('."'".$dt->id."'".')"><i class="glyphicon glyphicon-check"></i> Bayar</a>';
            $data[] = $row;
        }

        $output = array(
            "draw" => $this->input->post('draw'),
            "recordsTotal" => $this->pengiriman_so_model->count_all(),
            "recordsFiltered" => $this->pengiriman_so_model->count_filtered(),
            "data" => $data,
        );
        //output to json format
        echo json_encode($output);
    }

    public function get($id)
    {
       // echo $id;
       // die();
        $data = $this->hutang_model->get_by_id($id);
        $data  = array(
            'id' => $data->id,
            'kode_hutang' => $data->kode_hutang,
            'nomor_referensi' => $data->nomor_referensi,
            'kode_relasi' => $data->kode_relasi,
            'nama_relasi' => $data->nama_relasi,
            'nominal' => $data->nominal,
            
          //  'detailBarang'=> (array) $this->detail_barang_model->getDataByTransaksi($id)
        );
        echo json_encode(array($data));
    }

    public function add()
    {

        $tanggal_asli = explode("-",$this->tanggaldb($this->input->post('tanggal')));
        
        $jumlah_kirim = $this->pengiriman_so_model->total_pengiriman_so_perbulan_tahun($tanggal_asli[1],$tanggal_asli[0]); 
        
        if($jumlah_kirim == 0){
            $jumlah = 1;
            $kode_awal = "00001";
        }else{
            $jumlah = $jumlah_kirim + 1;

            if(strlen($jumlah_kirim) == 1 ){
                $kode_awal = "0000".$jumlah;
            }else if(strlen($jumlah_kirim) == 2){
                $kode_awal = "000".$jumlah;
            }else if(strlen($jumlah_kirim) == 3){
                $kode_awal = "00".$jumlah;
            }else if(strlen($jumlah_kirim) == 4){
                $kode_awal = "0".$jumlah;
            }else {
                $kode_awal = $jumlah;
            }
        }

        $pegawai = explode("-",$this->input->post('nama_pegawai'));

        $kode_kirim = $kode_awal."/PN/".$tanggal_asli[1]."/".$tanggal_asli[0];
        $id_rand = rand(1,100);
        $id = md5($id_rand.$kode_kirim.date("YmdHis"));
        $data = array(
            'id' => $id,
            'kode_pengiriman' => $kode_kirim,
            'kode_so' => $this->input->post('kode_so'),
            'tanggal' => $this->tanggaldb($this->input->post('tanggal')),
            'qty' => $this->input->post('qty_kirim'),
            'kode_barang' => $this->input->post('kode_barang'),
            'nama_barang' => $this->input->post('nama_barang'),
            'kode_kurir' => $pegawai[0],
            'nama_kurir' => $pegawai[1]
            
        );
        $insert = $this->pengiriman_so_model->save($data);

        /*
        $stok_barang = $this->stok_model->total_perbarang($this->input->post('kode_barang'));

        $stok_limit = $this->barang_model->total_limit_perbarang($this->input->post('kode_barang'));

        
        if($stok_barang - $this->input->post('qty_kirim') < $stok_limit){
            $status = 'Stok Limit';
        }else{
            $status = 'Stok Baik';
        }
        
        $data_stok = array(
            'status_stok' => $status,
            'qty' => $stok_barang - $this->input->post('qty_kirim')
        );

        $this->stok_model->update_by_kode($this->input->post('kode_barang'), $data_stok);
        
        */
        $so = $this->sales_order_model->get_by_noSO($this->input->post('kode_so'));

        $jumlah_piutang = $this->piutang_model->total_piutang_perbulan_tahun($tanggal_asli[1],$tanggal_asli[0]); 
        
        if($jumlah_piutang == 0){
            $jumlah_p = 1;
            $kode_awal = "00001";
        }else{
            $jumlah_p = $jumlah_piutang + 1;

            if(strlen($jumlah_piutang) == 1 ){
                $kode_awal = "0000".$jumlah_p;
            }else if(strlen($jumlah_piutang) == 2){
                $kode_awal = "000".$jumlah_p;
            }else if(strlen($jumlah_piutang) == 3){
                $kode_awal = "00".$jumlah_p;
            }else if(strlen($jumlah_piutang) == 4){
                $kode_awal = "0".$jumlah_p;
            }else {
                $kode_awal = $jumlah;
            }
        }

        $kode_piutang = $kode_awal."/PI/".$tanggal_asli[1]."/".$tanggal_asli[0];
        $rand_piutang = rand(1,100);
        $id_piutang = md5($kode_piutang.$rand_piutang.date("YmdHis").$jumlah_piutang);
        
        $jatuh_tempo = $this->penerimaan_po_model->get_by_jatuh_tempo($this->tanggaldb($this->input->post('tanggal')),$so->top);
        $data_piutang = array(
                'id' => $id_piutang,
                'kode_piutang' => $kode_piutang,
                'kode_referensi' => $so->kode_so,
                'kode_relasi' => $so->kode_customer,
                'nama_relasi' => $so->nama_customer,
                'jenis' => 'Penjualan',
                'nominal' => $this->input->post('qty_kirim') * $this->input->post('harga'),
                'tanggal' => $this->tanggaldb($this->input->post('tanggal')),
                'tanggal_jatuh_tempo' => $jatuh_tempo,
                'status' => 'Belum Lunas' ,
            );


        $this->piutang_model->insert($data_piutang);


        echo json_encode(array("status" => TRUE));
    }

    public function update()
    {
        $datax  = $this->input->post('dataDetail');
        $json = json_decode($datax);

        $gudang = $this->gudang_model->get($this->input->post('gudang_id'));

        $data = array(
            'barang_id' => $this->input->post('barang_id'),
            'keterangan' => $this->input->post('keterangan'),
            'nama_barang' => $this->input->post('nama_barang'),
            'qty' => $this->input->post('qty'),
            'gudang_id' => $this->input->post('gudang_id'),
            'nama_gudang' => $gudang->kode.'-'.$gudang->nama
            
        );
        $this->stok_model->update_by_id(array('id' => $this->input->post('id')), $data);



        echo json_encode(array("status" => TRUE));
    }

    public function delete($id)
    {
       /* $datay = $this->detail_barang_model->getDataByTransaksi($id);
        foreach ($datay as $rw) :
            $this->detail_barang_model->delete($rw['id']);
        endforeach;*/

        //$this->stok_fisik_model->delete($id);
        
        $data = $this->stok_fisik_model->get($id);

        print_r($data);
        die();
        $stok_barang = $this->stok_fisik_model->getJumlahStokBarang($data->barang_id);
        
        $stok_limit = $this->barang_model->get($data->barang_id);
        $jumlah_stok_limit = $stok_limit->batas_stok;

        if($stok_barang < $jumlah_stok_limit){
            $status = 'Stok Limit';
        }else{
            $status = 'Stok Baik';
        }

        $data_barang = array(
            'status_stok' => $status
        );

        $this->barang_model->update_by_id(array('id' => $data->barang_id), $data_barang);


        echo json_encode(array("status" => TRUE));
    }

    public function combo_jenis_barang(){
        $and_or = $this->input->get('and_or');
        $order_by = $this->input->get('order_by');
        $page_num= $this->input->get('page_num');
        $per_page= $this->input->get('per_page');
        $q_word= $this->input->get('q_word');
        $search_field= $this->input->get('search_field');

        $datanya = $this->barang_model->combo_jenis_barang($and_or,$order_by,$page_num,$per_page,$q_word,$search_field);
        echo json_encode($datanya);
    }

    public function stok($barang)
    {
        //$data = $this->barang_masuk_model->get($id);
        $data  = array(
            'detailStok'=> (array) $this->stok_fisik_model->getStokByBarangId($barang)
        );
        echo json_encode(array($data));
    }


}